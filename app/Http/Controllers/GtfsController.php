<?php

namespace App\Http\Controllers;

use App\Models\GtfsSetting;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use App\Models\GtfsTrip;
use Illuminate\Support\Facades\DB;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsRoute;
use App\Models\GtfsStopTime;
use App\Models\GtfsStop;
use App\Models\GtfsHeartbeat;
use Illuminate\Support\Facades\Auth;
use App\Jobs\DownloadAndProcessGtfs;
use App\Models\StopStatus;
use App\Models\TrainStatus;

class GtfsController extends Controller
{

    public function index()
    {
        $gtfsSettings = GtfsSetting::first();
        $groups = Group::all();

        return view('settings.gtfs', compact('gtfsSettings', 'groups'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url'
        ]);

        $gtfsSettings = GtfsSetting::firstOrNew();
        $gtfsSettings->url = $validated['url'];
        $gtfsSettings->save();

        return redirect()->route('settings.gtfs')->with('success', 'GTFS URL updated successfully.');
    }

    public function download()
    {
        $gtfsSettings = GtfsSetting::first();
        
        if (!$gtfsSettings) {
            return response()->json(['error' => 'GTFS settings not found'], 404);
        }

        if ($gtfsSettings->is_downloading) {
            return response()->json([
                'status' => 'in_progress',
                'elapsed_time' => $gtfsSettings->download_started_at->diffInSeconds(now()),
                'progress' => $gtfsSettings->download_progress ?? 0
            ]);
        }

        $gtfsSettings->is_downloading = true;
        $gtfsSettings->download_started_at = now();
        $gtfsSettings->download_progress = 0;
        $gtfsSettings->save();

        // Dispatch the download job
        dispatch(new \App\Jobs\DownloadAndProcessGtfs());

        return response()->json(['status' => 'started']);
    }

    public function clear()
    {
        $gtfsSettings = GtfsSetting::first();
        
        if (!$gtfsSettings) {
            return redirect()->route('settings.gtfs')->with('error', 'GTFS settings not found.');
        }

        // Clear all GTFS data
        \App\Models\GtfsRoute::truncate();
        \App\Models\GtfsTrip::truncate();
        \App\Models\GtfsStop::truncate();
        \App\Models\GtfsStopTime::truncate();
        \App\Models\GtfsCalendar::truncate();
        \App\Models\GtfsCalendarDate::truncate();

        $gtfsSettings->last_download = null;
        $gtfsSettings->next_download = null;
        $gtfsSettings->is_downloading = false;
        $gtfsSettings->download_progress = null;
        $gtfsSettings->download_started_at = null;
        $gtfsSettings->save();

        return redirect()->route('settings.gtfs')->with('success', 'GTFS data cleared successfully.');
    }

    private function removeDirectory($path) {
        if (!file_exists($path)) {
            return;
        }

        $files = glob($path . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }

    private function syncTripsData($extractPath) 
    {
        $tripsFile = $extractPath . '/trips.txt';
        if (!file_exists($tripsFile)) {
            throw new \Exception('trips.txt not found in GTFS data');
        }

        Log::info('Starting GTFS trips sync');

        // Read trips.txt file
        $file = fopen($tripsFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open trips.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read trips.txt headers');
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Keep track of processed trip_ids
            $processedTripIds = [];
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            // Process each line
            while (($data = fgetcsv($file)) !== FALSE) {
                $tripData = array_combine($headers, $data);
                $tripId = $tripData['trip_id'];
                $processedTripIds[] = $tripId;

                // Format data for database
                $formattedData = [
                    'route_id' => $tripData['route_id'],
                    'service_id' => $tripData['service_id'],
                    'trip_id' => $tripId,
                    'trip_headsign' => $tripData['trip_headsign'],
                    'trip_short_name' => $tripData['trip_short_name'],
                    'direction_id' => (int)$tripData['direction_id'],
                    'shape_id' => $tripData['shape_id'],
                    'wheelchair_accessible' => (bool)$tripData['wheelchair_accessible'],
                    'updated_at' => now()
                ];

                // Check if trip exists and if it needs updating
                $existingTrip = GtfsTrip::where('trip_id', $tripId)->first();
                
                if (!$existingTrip) {
                    // Create new trip
                    GtfsTrip::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($existingTrip->$key != $value && $key !== 'updated_at') {
                            $needsUpdate = true;
                            break;
                        }
                    }

                    if ($needsUpdate) {
                        $existingTrip->update($formattedData);
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }
            }

            // Remove trips that no longer exist in the file
            $deleted = GtfsTrip::whereNotIn('trip_id', $processedTripIds)->delete();

            DB::commit();

            Log::info('GTFS trips sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function syncCalendarDatesData($extractPath) 
    {
        $calendarDatesFile = $extractPath . '/calendar_dates.txt';
        if (!file_exists($calendarDatesFile)) {
            throw new \Exception('calendar_dates.txt not found in GTFS data');
        }

        Log::info('Starting GTFS calendar dates sync');
        
        $file = fopen($calendarDatesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open calendar_dates.txt');
        }

        try {
            $headers = fgetcsv($file);
            if (!$headers) {
                throw new \Exception('Unable to read calendar_dates.txt headers');
            }

            DB::beginTransaction();
            
            // Use delete instead of truncate to avoid implicit commit
            GtfsCalendarDate::query()->delete();
            
            $created = 0;
            $batch = [];
            $batchSize = 1000;

            while (($data = fgetcsv($file)) !== FALSE) {
                $calendarData = array_combine($headers, $data);
                
                // Convert YYYYMMDD to Y-m-d format
                $dateString = $calendarData['date'];
                $year = substr($dateString, 0, 4);
                $month = substr($dateString, 4, 2);
                $day = substr($dateString, 6, 2);
                $formattedDate = "{$year}-{$month}-{$day}";

                $batch[] = [
                    'service_id' => $calendarData['service_id'],
                    'date' => $formattedDate,
                    'exception_type' => (int)$calendarData['exception_type'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                $created++;

                if (count($batch) >= $batchSize) {
                    GtfsCalendarDate::insert($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (!empty($batch)) {
                GtfsCalendarDate::insert($batch);
            }

            DB::commit();
            
            Log::info('GTFS calendar dates sync completed', [
                'created' => $created
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function syncRoutesData($extractPath) 
    {
        $routesFile = $extractPath . '/routes.txt';
        if (!file_exists($routesFile)) {
            throw new \Exception('routes.txt not found in GTFS data');
        }

        Log::info('Starting GTFS routes sync');

        // Read routes.txt file
        $file = fopen($routesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open routes.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read routes.txt headers');
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Keep track of processed route_ids
            $processedRouteIds = [];
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            // Process each line
            while (($data = fgetcsv($file)) !== FALSE) {
                $routeData = array_combine($headers, $data);
                $routeId = $routeData['route_id'];
                $processedRouteIds[] = $routeId;

                // Format data for database
                $formattedData = [
                    'route_id' => $routeId,
                    'agency_id' => $routeData['agency_id'],
                    'route_short_name' => $routeData['route_short_name'],
                    'route_long_name' => $routeData['route_long_name'],
                    'route_type' => (int)$routeData['route_type'],
                    'route_color' => $routeData['route_color'] ?? null,
                    'route_text_color' => $routeData['route_text_color'] ?? null,
                    'updated_at' => now()
                ];

                // Check if route exists and if it needs updating
                $existingRoute = GtfsRoute::where('route_id', $routeId)->first();
                
                if (!$existingRoute) {
                    // Create new route
                    GtfsRoute::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($existingRoute->$key != $value && $key !== 'updated_at') {
                            $needsUpdate = true;
                            break;
                        }
                    }

                    if ($needsUpdate) {
                        $existingRoute->update($formattedData);
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }
            }

            // Remove routes that no longer exist in the file
            $deleted = GtfsRoute::whereNotIn('route_id', $processedRouteIds)->delete();

            DB::commit();

            Log::info('GTFS routes sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
    }

    public function syncStopTimesData($extractPath)
    {
        $filePath = $extractPath . '/stop_times.txt';
        if (!file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return;
        }

        // Map header columns
        $columns = array_flip($header);

        // Prepare batch insert
        $batch = [];
        $stopStatuses = [];

        while (($data = fgetcsv($handle)) !== FALSE) {
            $stopTime = [
                'trip_id' => $data[$columns['trip_id']],
                'arrival_time' => $data[$columns['arrival_time']],
                'departure_time' => $data[$columns['departure_time']],
                'stop_id' => $data[$columns['stop_id']],
                'stop_sequence' => $data[$columns['stop_sequence']],
                'pickup_type' => $data[$columns['pickup_type']] ?? 0,
                'drop_off_type' => $data[$columns['drop_off_type']] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $batch[] = $stopTime;

            // Create initial stop status
            $stopStatuses[] = [
                'trip_id' => $data[$columns['trip_id']],
                'stop_id' => $data[$columns['stop_id']],
                'status' => 'on-time',
                'status_color' => '156,163,175',
                'status_color_hex' => '#9CA3AF',
                'scheduled_arrival_time' => $data[$columns['arrival_time']],
                'scheduled_departure_time' => $data[$columns['departure_time']],
                'departure_platform' => 'TBD',
                'arrival_platform' => 'TBD',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                GtfsStopTime::insert($batch);
                StopStatus::insert($stopStatuses);
                $batch = [];
                $stopStatuses = [];
            }
        }

        if (!empty($batch)) {
            GtfsStopTime::insert($batch);
        }

        if (!empty($stopStatuses)) {
            StopStatus::insert($stopStatuses);
        }

        fclose($handle);
    }

    public function updateStopStatus(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|string',
            'stop_id' => 'required|string',
            'status' => 'required|string|in:on-time,delayed,cancelled,completed',
            'actual_arrival_time' => 'nullable|date_format:H:i:s',
            'actual_departure_time' => 'nullable|date_format:H:i:s',
            'platform_code' => 'nullable|string',
            'departure_platform' => 'nullable|string',
            'arrival_platform' => 'nullable|string'
        ]);

        $stopStatus = StopStatus::updateOrCreate(
            [
                'trip_id' => $request->trip_id,
                'stop_id' => $request->stop_id,
            ],
            [
                'status' => $request->status,
                'actual_arrival_time' => $request->actual_arrival_time,
                'actual_departure_time' => $request->actual_departure_time,
                'platform_code' => $request->platform_code,
                'departure_platform' => $request->departure_platform,
                'arrival_platform' => $request->arrival_platform,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $stopStatus
        ]);
    }

    public function getStopStatuses($tripId)
    {
        $stopStatuses = StopStatus::with(['stop'])
            ->where('trip_id', $tripId)
            ->orderBy('scheduled_arrival_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stopStatuses
        ]);
    }

    private function syncStopsData($extractPath) 
    {
        $stopsFile = $extractPath . '/stops.txt';
        if (!file_exists($stopsFile)) {
            throw new \Exception('stops.txt not found in GTFS data');
        }

        Log::info('Starting GTFS stops sync');

        // Read stops.txt file
        $file = fopen($stopsFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open stops.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read stops.txt headers');
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Keep track of processed stop_ids
            $processedStopIds = [];
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            // Process each line
            while (($data = fgetcsv($file)) !== FALSE) {
                $stopData = array_combine($headers, $data);
                $stopId = $stopData['stop_id'];
                $processedStopIds[] = $stopId;

                // Format data for database
                $formattedData = [
                    'stop_id' => $stopId,
                    'stop_code' => $stopData['stop_code'] ?: null,
                    'stop_name' => $stopData['stop_name'],
                    'stop_lon' => (float)$stopData['stop_lon'],
                    'stop_lat' => (float)$stopData['stop_lat'],
                    'stop_timezone' => $stopData['stop_timezone'] ?: null,
                    'location_type' => (int)($stopData['location_type'] ?? 0),
                    'platform_code' => $stopData['platform_code'] ?? null,
                    'updated_at' => now()
                ];

                // Check if stop exists and if it needs updating
                $existingStop = GtfsStop::where('stop_id', $stopId)->first();
                
                if (!$existingStop) {
                    // Create new stop
                    GtfsStop::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($existingStop->$key != $value && $key !== 'updated_at') {
                            $needsUpdate = true;
                            break;
                        }
                    }

                    if ($needsUpdate) {
                        $existingStop->update($formattedData);
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }
            }

            // Remove stops that no longer exist in the file
            $deleted = GtfsStop::whereNotIn('stop_id', $processedStopIds)->delete();

            DB::commit();

            Log::info('GTFS stops sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function formatTime($time)
    {
        // Handle times that might go beyond 24 hours
        $parts = explode(':', $time);
        $hours = (int)$parts[0];
        
        // If hours are greater than 23, adjust to fit within 24-hour format
        // This might not be ideal for all use cases, but prevents database errors
        if ($hours > 23) {
            $hours = $hours % 24;
            $parts[0] = str_pad($hours, 2, '0', STR_PAD_LEFT);
            $time = implode(':', $parts);
        }
        
        return $time;
    }

    public function updateTrainStatus(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|string',
            'status' => 'required|string'
        ]);

        $trainStatus = TrainStatus::updateOrCreate(
            ['trip_id' => $request->trip_id],
            ['status' => $request->status]
        );

        return response()->json([
            'success' => true,
            'data' => $trainStatus
        ]);
    }

    public function getTrainStatus($tripId)
    {
        $trainStatus = TrainStatus::where('trip_id', $tripId)->first();

        return response()->json([
            'success' => true,
            'data' => $trainStatus
        ]);
    }

}
