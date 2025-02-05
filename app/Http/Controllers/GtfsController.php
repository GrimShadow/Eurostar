<?php

namespace App\Http\Controllers;

use App\Models\GtfsSetting;
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

class GtfsController extends Controller
{

    public function viewGtfs()
    {
        $gtfsSettings = GtfsSetting::first();
        return view('settings.gtfs', compact('gtfsSettings'));
    }

    public function updateGtfsUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        Log::info('GTFS URL being saved: ' . $request->url);

        GtfsSetting::updateOrCreate(
            ['id' => 1],
            [
                'url' => $request->url,
                'is_active' => true,
                'next_download' => now()->addDay()
            ]
        );

        return redirect()->route('settings.gtfs')->with('success', 'GTFS URL updated successfully');
    }

    public function downloadGtfs()
    {
        $settings = GtfsSetting::first();
        
        if (!$settings) {
            return redirect()->route('settings.gtfs')->with('error', 'GTFS URL not configured');
        }

        try {
            // Download the ZIP file
            Log::info('Downloading GTFS file from: ' . $settings->url);
            $response = Http::timeout(30)->get($settings->url);
            
            if ($response->successful()) {
                // Ensure storage directories exist with proper permissions
                $storagePath = storage_path('app/gtfs');
                Log::info('Storage path: ' . $storagePath);
                $zipPath = $storagePath . '/latest.zip';
                Log::info('ZIP path: ' . $zipPath);
                $extractPath = $storagePath . '/current';
                Log::info('Extract path: ' . $extractPath);
                
                // Create directories if they don't exist
                if (!file_exists($storagePath)) {
                    mkdir($storagePath, 0755, true);
                }
                
                // Save the ZIP content to a file
                Log::info('Saving ZIP file to: ' . $zipPath);
                file_put_contents($zipPath, $response->body());
                
                // Verify the ZIP file exists and is readable
                if (!file_exists($zipPath)) {
                    throw new \Exception('ZIP file was not saved properly');
                }
                
                Log::info('ZIP file size: ' . filesize($zipPath) . ' bytes');

                // Check if it's a valid ZIP file
                if (!class_exists('ZipArchive')) {
                    throw new \Exception('ZipArchive class is not available. Please install php-zip extension');
                }

                $zip = new ZipArchive();
                $openResult = $zip->open($zipPath);
                
                if ($openResult !== TRUE) {
                    throw new \Exception('Failed to open ZIP file. Error code: ' . $openResult);
                }

                // Clear existing directory
                if (file_exists($extractPath)) {
                    Log::info('Removing existing directory: ' . $extractPath);
                    $this->removeDirectory($extractPath);
                }

                // Create fresh directory
                Log::info('Creating extraction directory: ' . $extractPath);
                if (!mkdir($extractPath, 0755, true)) {
                    throw new \Exception('Failed to create extraction directory');
                }

                // Extract files
                Log::info('Extracting ZIP file to: ' . $extractPath);
                if (!$zip->extractTo($extractPath)) {
                    throw new \Exception('Failed to extract files. ZIP error: ' . $zip->getStatusString());
                }

                if ($zip->close()) {
                    // List extracted files
                    $files = scandir($extractPath);
                    Log::info('Extracted files:', array_diff($files, ['.', '..']));

                    // Sync all GTFS data
                    $this->syncTripsData($extractPath);
                    $this->syncCalendarDatesData($extractPath);
                    $this->syncRoutesData($extractPath);
                    $this->syncStopTimesData($extractPath);
                    $this->syncStopsData($extractPath);

                    // Update settings
                    $settings->update([
                        'last_download' => now(),
                        'next_download' => now()->addDay()
                    ]);

                    return redirect()->route('settings.gtfs')
                        ->with('success', 'GTFS data downloaded and synced successfully');
                }
            }
            
            throw new \Exception('Failed to download file. Status: ' . $response->status());

        } catch (\Exception $e) {
            Log::error('GTFS download/extract failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('settings.gtfs')
                ->with('error', 'Error: ' . $e->getMessage());
        }
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

    private function syncStopTimesData($extractPath) 
    {
        $stopTimesFile = $extractPath . '/stop_times.txt';
        if (!file_exists($stopTimesFile)) {
            throw new \Exception('stop_times.txt not found in GTFS data');
        }

        Log::info('Starting GTFS stop times sync');

        // Read stop_times.txt file
        $file = fopen($stopTimesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open stop_times.txt');
        }

        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read stop_times.txt headers');
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Clear all existing records first (more efficient for stop times)
            GtfsStopTime::query()->delete();
            $created = 0;
            $batchSize = 1000;
            $batch = [];

            // Process each line
            while (($data = fgetcsv($file)) !== FALSE) {
                $stopTimeData = array_combine($headers, $data);
                
                // Format data for database
                $formattedData = [
                    'trip_id' => $stopTimeData['trip_id'],
                    'arrival_time' => $this->formatTime($stopTimeData['arrival_time']),
                    'departure_time' => $this->formatTime($stopTimeData['departure_time']),
                    'stop_id' => $stopTimeData['stop_id'],
                    'stop_sequence' => (int)$stopTimeData['stop_sequence'],
                    'drop_off_type' => (int)($stopTimeData['drop_off_type'] ?? 0),
                    'pickup_type' => (int)($stopTimeData['pickup_type'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $batch[] = $formattedData;
                $created++;

                // Insert in batches for better performance
                if (count($batch) >= $batchSize) {
                    GtfsStopTime::insert($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (!empty($batch)) {
                GtfsStopTime::insert($batch);
            }

            DB::commit();

            Log::info('GTFS stop times sync completed', [
                'created' => $created
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
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

    public function clearGtfsData()
    {
        try {
            // Disable foreign key checks for MySQL
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            
            // Clear all GTFS-related tables
            DB::table('gtfs_trips')->truncate();
            DB::table('gtfs_calendar_dates')->truncate();
            DB::table('gtfs_routes')->truncate();
            DB::table('gtfs_stop_times')->truncate();
            DB::table('gtfs_stops')->truncate();
            DB::table('gtfs_heartbeats')->truncate();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            
            // Update the last_download timestamp to null
            if ($settings = GtfsSetting::first()) {
                $settings->update([
                    'last_download' => null,
                    'next_download' => null
                ]);
            }

            return redirect()->route('settings.gtfs')
                ->with('success', 'All GTFS data has been cleared successfully.');
        } catch (\Exception $e) {
            // Re-enable foreign key checks even if there's an error
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            
            Log::error('Failed to clear GTFS data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('settings.gtfs')
                ->with('error', 'Failed to clear GTFS data: ' . $e->getMessage());
        }
    }

}
