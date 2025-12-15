<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsRoute;
use App\Models\GtfsSetting;
use App\Models\GtfsStop;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Models\StopStatus;
use App\Models\TrainStatus;
use App\Services\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

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
            'url' => 'required|url',
            'realtime_url' => 'nullable|url',
            'realtime_update_interval' => 'nullable|integer|min:10|max:300',
        ]);

        $gtfsSettings = GtfsSetting::firstOrNew();
        $gtfsSettings->url = $validated['url'];
        $gtfsSettings->realtime_url = $validated['realtime_url'];
        $gtfsSettings->realtime_update_interval = $validated['realtime_update_interval'] ?? 30;
        $gtfsSettings->save();

        return redirect()->route('settings.gtfs')->with('success', 'GTFS settings updated successfully.');
    }

    public function download()
    {
        $gtfsSettings = GtfsSetting::first();

        if (! $gtfsSettings) {
            return response()->json(['error' => 'GTFS settings not found'], 404);
        }

        // Check if download has been stuck for more than 1 hour
        if ($gtfsSettings->is_downloading) {
            $elapsedTime = $gtfsSettings->download_started_at->diffInSeconds(now());

            // If stuck for more than 1 hour, reset the state
            if ($elapsedTime > 3600) {
                LogHelper::gtfsDebug('GTFS download appears stuck, resetting state', [
                    'elapsed_time' => $elapsedTime,
                    'started_at' => $gtfsSettings->download_started_at,
                    'progress' => $gtfsSettings->download_progress,
                ]);

                $gtfsSettings->is_downloading = false;
                $gtfsSettings->download_progress = 0;
                $gtfsSettings->download_status = null;
                $gtfsSettings->save();
            } else {
                return response()->json([
                    'status' => 'in_progress',
                    'elapsed_time' => $elapsedTime,
                    'progress' => $gtfsSettings->download_progress ?? 0,
                ]);
            }
        }

        $gtfsSettings->is_downloading = true;
        $gtfsSettings->download_started_at = now();
        $gtfsSettings->download_progress = 0;
        $gtfsSettings->download_status = 'Starting download...';
        $gtfsSettings->next_download = now()->addDay()->setTime(3, 0, 0);
        $gtfsSettings->save();

        // Dispatch the download job
        dispatch(new \App\Jobs\DownloadAndProcessGtfs);

        return response()->json(['status' => 'started']);
    }

    public function resetDownload()
    {
        $gtfsSettings = GtfsSetting::first();

        if (! $gtfsSettings) {
            return response()->json(['error' => 'GTFS settings not found'], 404);
        }

        $gtfsSettings->is_downloading = false;
        $gtfsSettings->download_progress = 0;
        $gtfsSettings->download_status = null;
        $gtfsSettings->save();

        LogHelper::gtfsInfo('GTFS download state manually reset');

        return response()->json(['status' => 'reset', 'message' => 'Download state has been reset']);
    }

    public function progress()
    {
        $gtfsSettings = GtfsSetting::first();

        if (! $gtfsSettings) {
            return response()->json(['error' => 'GTFS settings not found'], 404);
        }

        return response()->json([
            'is_downloading' => $gtfsSettings->is_downloading,
            'progress' => $gtfsSettings->download_progress ?? 0,
            'status' => $gtfsSettings->download_status,
            'started_at' => $gtfsSettings->download_started_at?->format('Y-m-d H:i:s'),
            'elapsed_time' => $gtfsSettings->download_started_at ? $gtfsSettings->download_started_at->diffInSeconds(now()) : 0,
        ]);
    }

    public function clear()
    {
        $gtfsSettings = GtfsSetting::first();

        if (! $gtfsSettings) {
            error_log('GTFS settings not found');

            return redirect()->route('settings.gtfs')->with('error', 'GTFS settings not found.');
        }

        try {
            error_log('Starting GTFS data clear');

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Clear tables using query builder
            $tables = [
                'gtfs_stop_times',
                'gtfs_trips',
                'gtfs_routes',
                'gtfs_stops',
                'gtfs_calendar_dates',
                'stop_statuses',
                'train_statuses',
            ];

            foreach ($tables as $table) {
                try {
                    // First check if table exists
                    if (Schema::hasTable($table)) {
                        // Get count before clearing
                        $count = DB::table($table)->count();
                        error_log("Table {$table} has {$count} records before clearing");

                        // Clear the table
                        DB::table($table)->truncate();

                        // Verify it's empty
                        $newCount = DB::table($table)->count();
                        error_log("Table {$table} has {$newCount} records after clearing");

                        if ($newCount > 0) {
                            throw new \Exception("Table {$table} still has {$newCount} records after truncate");
                        }
                    } else {
                        throw new \Exception("Table {$table} does not exist");
                    }
                } catch (\Exception $e) {
                    error_log("Error clearing table {$table}: ".$e->getMessage());
                    throw $e;
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Reset GTFS settings
            $gtfsSettings->last_download = null;
            $gtfsSettings->next_download = null;
            $gtfsSettings->is_downloading = false;
            $gtfsSettings->download_progress = null;
            $gtfsSettings->download_started_at = null;
            $gtfsSettings->download_status = null;
            $gtfsSettings->save();

            error_log('GTFS data cleared successfully');

            return redirect()->route('settings.gtfs')->with('success', 'GTFS data cleared successfully.');
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $error = 'Failed to clear GTFS data: '.$e->getMessage();
            error_log($error);

            return redirect()->route('settings.gtfs')->with('error', $error);
        }
    }

    private function removeDirectory($path)
    {
        if (! file_exists($path)) {
            return;
        }

        $files = glob($path.'/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($path);
    }

    public function syncTripsData($extractPath)
    {
        $tripsFile = $extractPath.'/trips.txt';
        if (! file_exists($tripsFile)) {
            throw new \Exception('trips.txt not found in GTFS data');
        }

        LogHelper::gtfsInfo('Starting GTFS trips sync');

        // Read trips.txt file
        $file = fopen($tripsFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open trips.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (! $headers) {
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
            while (($data = fgetcsv($file)) !== false) {
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
                    'direction_id' => (int) $tripData['direction_id'],
                    'shape_id' => $tripData['shape_id'],
                    'wheelchair_accessible' => (bool) $tripData['wheelchair_accessible'],
                    'updated_at' => now(),
                ];

                // Check if trip exists and if it needs updating
                $existingTrip = GtfsTrip::where('trip_id', $tripId)->first();

                if (! $existingTrip) {
                    // Create new trip
                    GtfsTrip::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($value != $existingTrip->$key && $key !== 'updated_at') {
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

            LogHelper::gtfsInfo('GTFS trips sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
    }

    public function syncCalendarDatesData($extractPath)
    {
        $calendarDatesFile = $extractPath.'/calendar_dates.txt';
        if (! file_exists($calendarDatesFile)) {
            throw new \Exception('calendar_dates.txt not found in GTFS data');
        }

        LogHelper::gtfsInfo('Starting GTFS calendar dates sync');

        $file = fopen($calendarDatesFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open calendar_dates.txt');
        }

        try {
            $headers = fgetcsv($file);
            if (! $headers) {
                throw new \Exception('Unable to read calendar_dates.txt headers');
            }

            DB::beginTransaction();

            // Use delete instead of truncate to avoid implicit commit
            GtfsCalendarDate::query()->delete();

            $created = 0;
            $batch = [];
            $batchSize = 1000;

            while (($data = fgetcsv($file)) !== false) {
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
                    'exception_type' => (int) $calendarData['exception_type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $created++;

                if (count($batch) >= $batchSize) {
                    GtfsCalendarDate::insert($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (! empty($batch)) {
                GtfsCalendarDate::insert($batch);
            }

            DB::commit();

            LogHelper::gtfsInfo('GTFS calendar dates sync completed', [
                'created' => $created,
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

    public function syncRoutesData($extractPath)
    {
        $routesFile = $extractPath.'/routes.txt';
        if (! file_exists($routesFile)) {
            throw new \Exception('routes.txt not found in GTFS data');
        }

        LogHelper::gtfsInfo('Starting GTFS routes sync');

        // Read routes.txt file
        $file = fopen($routesFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open routes.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (! $headers) {
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
            while (($data = fgetcsv($file)) !== false) {
                $routeData = array_combine($headers, $data);
                $routeId = $routeData['route_id'];
                $processedRouteIds[] = $routeId;

                // Format data for database
                $formattedData = [
                    'route_id' => $routeId,
                    'agency_id' => $routeData['agency_id'],
                    'route_short_name' => $routeData['route_short_name'],
                    'route_long_name' => $routeData['route_long_name'],
                    'route_type' => (int) $routeData['route_type'],
                    'route_color' => $routeData['route_color'] ?? null,
                    'route_text_color' => $routeData['route_text_color'] ?? null,
                    'updated_at' => now(),
                ];

                // Check if route exists and if it needs updating
                $existingRoute = GtfsRoute::where('route_id', $routeId)->first();

                if (! $existingRoute) {
                    // Create new route
                    GtfsRoute::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($value != $existingRoute->$key && $key !== 'updated_at') {
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

            LogHelper::gtfsInfo('GTFS routes sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
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
        $filePath = $extractPath.'/stop_times.txt';
        if (! file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return;
        }

        // Read header
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return;
        }

        // Map header columns
        $columns = array_flip($header);

        // Prepare batch insert
        $batch = [];
        $processedStopStatuses = [];

        while (($data = fgetcsv($handle)) !== false) {
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

            // Create or update stop status
            $stopStatusKey = $data[$columns['trip_id']].'-'.$data[$columns['stop_id']];
            if (! in_array($stopStatusKey, $processedStopStatuses)) {
                StopStatus::updateOrCreate(
                    [
                        'trip_id' => $data[$columns['trip_id']],
                        'stop_id' => $data[$columns['stop_id']],
                    ],
                    [
                        'status' => 'on-time',
                        'status_color' => '156,163,175',
                        'status_color_hex' => '#9CA3AF',
                        'scheduled_arrival_time' => $data[$columns['arrival_time']],
                        'scheduled_departure_time' => $data[$columns['departure_time']],
                        'departure_platform' => 'TBD',
                        'arrival_platform' => 'TBD',
                        'updated_at' => now(),
                    ]
                );
                $processedStopStatuses[] = $stopStatusKey;
            }

            if (count($batch) >= 1000) {
                GtfsStopTime::insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            GtfsStopTime::insert($batch);
        }

        fclose($handle);
    }

    /**
     * Convert RGB color string to hex format
     */
    private function rgbToHex($rgb)
    {
        if (empty($rgb)) {
            return '#9CA3AF'; // Default gray color
        }

        $rgbArray = explode(',', $rgb);
        if (count($rgbArray) !== 3) {
            return '#9CA3AF'; // Default gray color if invalid format
        }

        $hex = '#';
        foreach ($rgbArray as $component) {
            $hex .= str_pad(dechex(trim($component)), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($hex);
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
            'arrival_platform' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Get the status object to retrieve color information
            $statusObj = \App\Models\Status::where('status', $request->status)->first();

            $stopStatus = StopStatus::updateOrCreate(
                [
                    'trip_id' => $request->trip_id,
                    'stop_id' => $request->stop_id,
                ],
                [
                    'status' => $request->status,
                    'status_color' => $statusObj?->color_rgb ?? '156,163,175',
                    'status_color_hex' => $statusObj ? $this->rgbToHex($statusObj->color_rgb) : '#9CA3AF',
                    'actual_arrival_time' => $request->actual_arrival_time,
                    'actual_departure_time' => $request->actual_departure_time,
                    'platform_code' => $request->platform_code,
                    'departure_platform' => $request->departure_platform,
                    'arrival_platform' => $request->arrival_platform,
                ]
            );

            // Fire the same event that automated updates fire for consistency
            event(new \App\Events\TrainStatusUpdated($request->trip_id, $request->status));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $stopStatus,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::gtfsError('Failed to update stop status', [
                'trip_id' => $request->trip_id,
                'stop_id' => $request->stop_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update stop status',
            ], 500);
        }
    }

    public function getStopStatuses($tripId)
    {
        $stopStatuses = StopStatus::with(['stop'])
            ->where('trip_id', $tripId)
            ->orderBy('scheduled_arrival_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stopStatuses,
        ]);
    }

    public function syncStopsData($extractPath)
    {
        $stopsFile = $extractPath.'/stops.txt';
        if (! file_exists($stopsFile)) {
            throw new \Exception('stops.txt not found in GTFS data');
        }

        LogHelper::gtfsInfo('Starting GTFS stops sync');

        // Read stops.txt file
        $file = fopen($stopsFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open stops.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (! $headers) {
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
            while (($data = fgetcsv($file)) !== false) {
                $stopData = array_combine($headers, $data);
                $stopId = $stopData['stop_id'];
                $processedStopIds[] = $stopId;

                // Format data for database
                $formattedData = [
                    'stop_id' => $stopId,
                    'stop_code' => $stopData['stop_code'] ?: null,
                    'stop_name' => $stopData['stop_name'],
                    'stop_lon' => (float) $stopData['stop_lon'],
                    'stop_lat' => (float) $stopData['stop_lat'],
                    'stop_timezone' => $stopData['stop_timezone'] ?: null,
                    'location_type' => (int) ($stopData['location_type'] ?? 0),
                    'platform_code' => $stopData['platform_code'] ?? null,
                    'updated_at' => now(),
                ];

                // Check if stop exists and if it needs updating
                $existingStop = GtfsStop::where('stop_id', $stopId)->first();

                if (! $existingStop) {
                    // Create new stop
                    GtfsStop::create($formattedData);
                    $created++;
                } else {
                    // Check if data is different
                    $needsUpdate = false;
                    foreach ($formattedData as $key => $value) {
                        if ($value != $existingStop->$key && $key !== 'updated_at') {
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

            LogHelper::gtfsInfo('GTFS stops sync completed', [
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
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
        $hours = (int) $parts[0];

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
            'status' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Get the status object to retrieve color information
            $statusObj = \App\Models\Status::where('status', $request->status)->first();

            $trainStatus = TrainStatus::updateOrCreate(
                ['trip_id' => $request->trip_id],
                ['status' => $request->status]
            );

            // Also update any existing stop statuses for this train with the new colors
            StopStatus::where('trip_id', $request->trip_id)->update([
                'status' => $request->status,
                'status_color' => $statusObj?->color_rgb ?? '156,163,175',
                'status_color_hex' => $statusObj ? $this->rgbToHex($statusObj->color_rgb) : '#9CA3AF',
            ]);

            // Fire the same event that automated updates fire for consistency
            event(new \App\Events\TrainStatusUpdated($request->trip_id, $request->status));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $trainStatus,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::gtfsError('Failed to update train status', [
                'trip_id' => $request->trip_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update train status',
            ], 500);
        }
    }

    public function getTrainStatus($tripId)
    {
        $trainStatus = TrainStatus::where('trip_id', $tripId)->first();

        return response()->json([
            'success' => true,
            'data' => $trainStatus,
        ]);
    }

    public function testRealtime()
    {
        $gtfsSettings = GtfsSetting::first();

        if (! $gtfsSettings || ! $gtfsSettings->realtime_url) {
            return response()->json([
                'success' => false,
                'message' => 'No realtime URL configured',
            ], 400);
        }

        try {
            $response = Http::timeout(30)->withOptions(['force_ip_resolve' => '4'])->get($gtfsSettings->realtime_url);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch realtime data: HTTP '.$response->status(),
                ], 400);
            }

            $data = $response->json();

            if (! isset($data['entity'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid GTFS realtime format: missing entity array',
                ], 400);
            }

            $entitiesCount = count($data['entity']);

            // Update the last realtime update timestamp
            $gtfsSettings->last_realtime_update = now();
            $gtfsSettings->realtime_status = 'Connected - '.$entitiesCount.' entities';
            $gtfsSettings->save();

            return response()->json([
                'success' => true,
                'message' => 'Realtime feed test successful',
                'entities_count' => $entitiesCount,
            ]);

        } catch (\Exception $e) {
            // Update status with error
            $gtfsSettings->realtime_status = 'Error: '.$e->getMessage();
            $gtfsSettings->save();

            return response()->json([
                'success' => false,
                'message' => 'Failed to test realtime feed: '.$e->getMessage(),
            ], 500);
        }
    }
}
