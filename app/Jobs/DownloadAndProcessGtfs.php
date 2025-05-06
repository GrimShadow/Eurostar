<?php

namespace App\Jobs;

use App\Models\GtfsSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\DB;
use App\Models\GtfsTrip;
use App\Models\GtfsCalendarDate;
use App\Models\GtfsRoute;
use App\Models\GtfsStopTime;
use App\Models\GtfsStop;
use Illuminate\Support\Facades\Storage;

class DownloadAndProcessGtfs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60; // 1 minute

    private function updateProgress($settings, $progress, $status)
    {
        $settings->update([
            'download_progress' => $progress,
            'download_status' => $status
        ]);
    }

    public function handle()
    {
        try {
            $gtfsSettings = GtfsSetting::first();
            
            if (!$gtfsSettings) {
                throw new \Exception('GTFS settings not found');
            }

            // Download the file
            $response = Http::timeout(300)->get($gtfsSettings->url);
            if (!$response->successful()) {
                throw new \Exception('Failed to download GTFS data: ' . $response->status());
            }

            // Save the file
            $zipPath = storage_path('app/gtfs.zip');
            file_put_contents($zipPath, $response->body());

            // Extract the file
            $extractPath = storage_path('app/gtfs');
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== TRUE) {
                throw new \Exception('Failed to open GTFS zip file');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Process each file
            $this->processFile($extractPath, 'stops.txt', 'syncStopsData');
            $this->processFile($extractPath, 'routes.txt', 'syncRoutesData');
            $this->processFile($extractPath, 'trips.txt', 'syncTripsData');
            $this->processFile($extractPath, 'stop_times.txt', 'syncStopTimesData');
            $this->processFile($extractPath, 'calendar_dates.txt', 'syncCalendarDatesData');

            // Update settings
            $gtfsSettings->last_download = now();
            $gtfsSettings->is_downloading = false;
            $gtfsSettings->download_progress = 100;
            $gtfsSettings->save();

            // Clean up
            unlink($zipPath);
            $this->removeDirectory($extractPath);

        } catch (\Exception $e) {
            Log::error('GTFS download failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Update settings to reflect failure
            if (isset($gtfsSettings)) {
                $gtfsSettings->is_downloading = false;
                $gtfsSettings->download_progress = 0;
                $gtfsSettings->save();
            }

            // Rethrow the exception to trigger a retry
            throw $e;
        }
    }

    private function processFile($extractPath, $filename, $method)
    {
        try {
            $gtfsSettings = GtfsSetting::first();
            $gtfsSettings->download_progress = 0;
            $gtfsSettings->save();

            $controller = new \App\Http\Controllers\GtfsController();
            $controller->$method($extractPath);

            $gtfsSettings->download_progress = 100;
            $gtfsSettings->save();
        } catch (\Exception $e) {
            Log::error("Failed to process {$filename}: " . $e->getMessage());
            throw $e;
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
        Log::info('Trips file path: ' . $tripsFile);
        Log::info('File permissions: ' . substr(sprintf('%o', fileperms($tripsFile)), -4));

        $file = fopen($tripsFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open trips.txt. Error: ' . error_get_last()['message']);
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read trips.txt headers. Error: ' . error_get_last()['message']);
        }

        Log::info('Trips headers: ' . implode(', ', $headers));

        // Get total lines for progress calculation
        $totalLines = 0;
        while (fgets($file) !== false) {
            $totalLines++;
        }
        rewind($file);
        fgetcsv($file); // Skip headers

        DB::beginTransaction();
        try {
            $processedTripIds = [];
            $batchSize = 1000;
            $batch = [];
            $currentLine = 0;
            $created = 0;
            $updated = 0;

            while (($data = fgetcsv($file)) !== FALSE) {
                $currentLine++;
                $progress = 20 + (($currentLine / $totalLines) * 20); // 20-40% range
                $settings = GtfsSetting::first();
                $this->updateProgress($settings, $progress, "Processing trips: {$currentLine}/{$totalLines}");

                try {
                    $tripData = array_combine($headers, $data);
                    $tripId = $tripData['trip_id'];
                    $processedTripIds[] = $tripId;

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

                    $batch[] = $formattedData;

                    if (count($batch) >= $batchSize) {
                        $this->processBatch($batch, 'trips');
                        $created += count($batch);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing trip line ' . $currentLine, [
                        'error' => $e->getMessage(),
                        'data' => $data,
                        'headers' => $headers
                    ]);
                    throw $e;
                }
            }

            if (!empty($batch)) {
                $this->processBatch($batch, 'trips');
                $created += count($batch);
            }

            GtfsTrip::whereNotIn('trip_id', $processedTripIds)->delete();
            DB::commit();
            
            Log::info('GTFS trips sync completed', [
                'total_lines' => $totalLines,
                'created' => $created,
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GTFS trips sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
        Log::info('Calendar dates file path: ' . $calendarDatesFile);
        Log::info('File permissions: ' . substr(sprintf('%o', fileperms($calendarDatesFile)), -4));
        
        $file = fopen($calendarDatesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open calendar_dates.txt. Error: ' . error_get_last()['message']);
        }

        try {
            $headers = fgetcsv($file);
            if (!$headers) {
                throw new \Exception('Unable to read calendar_dates.txt headers. Error: ' . error_get_last()['message']);
            }

            Log::info('Calendar dates headers: ' . implode(', ', $headers));

            DB::beginTransaction();
            
            // Use delete instead of truncate to avoid implicit commit
            GtfsCalendarDate::query()->delete();
            
            $created = 0;
            $batch = [];
            $batchSize = 1000;

            while (($data = fgetcsv($file)) !== FALSE) {
                try {
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
                } catch (\Exception $e) {
                    Log::error('Error processing calendar date line', [
                        'error' => $e->getMessage(),
                        'data' => $data,
                        'headers' => $headers
                    ]);
                    throw $e;
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
            Log::error('GTFS calendar dates sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
        Log::info('Routes file path: ' . $routesFile);
        Log::info('File permissions: ' . substr(sprintf('%o', fileperms($routesFile)), -4));

        $file = fopen($routesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open routes.txt. Error: ' . error_get_last()['message']);
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read routes.txt headers. Error: ' . error_get_last()['message']);
        }

        Log::info('Routes headers: ' . implode(', ', $headers));

        DB::beginTransaction();
        try {
            $processedRouteIds = [];
            $batchSize = 1000;
            $batch = [];
            $created = 0;
            $updated = 0;

            while (($data = fgetcsv($file)) !== FALSE) {
                try {
                    $routeData = array_combine($headers, $data);
                    $routeId = $routeData['route_id'];
                    $processedRouteIds[] = $routeId;

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

                    $batch[] = $formattedData;

                    if (count($batch) >= $batchSize) {
                        $this->processBatch($batch, 'routes');
                        $created += count($batch);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing route line', [
                        'error' => $e->getMessage(),
                        'data' => $data,
                        'headers' => $headers
                    ]);
                    throw $e;
                }
            }

            if (!empty($batch)) {
                $this->processBatch($batch, 'routes');
                $created += count($batch);
            }

            GtfsRoute::whereNotIn('route_id', $processedRouteIds)->delete();
            DB::commit();
            
            Log::info('GTFS routes sync completed', [
                'created' => $created,
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GTFS routes sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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

        $file = fopen($stopTimesFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open stop_times.txt');
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read stop_times.txt headers');
        }

        DB::beginTransaction();
        try {
            // Clear all existing records first (more efficient for stop times)
            GtfsStopTime::query()->delete();
            $created = 0;
            $batchSize = 1000;
            $batch = [];

            while (($data = fgetcsv($file)) !== FALSE) {
                $stopTimeData = array_combine($headers, $data);
                
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

                if (count($batch) >= $batchSize) {
                    GtfsStopTime::insert($batch);
                    $batch = [];
                }
            }

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

        $file = fopen($stopsFile, 'r');
        if (!$file) {
            throw new \Exception('Unable to open stops.txt');
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Unable to read stops.txt headers');
        }

        DB::beginTransaction();
        try {
            $processedStopIds = [];
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            while (($data = fgetcsv($file)) !== FALSE) {
                $stopData = array_combine($headers, $data);
                $stopId = $stopData['stop_id'];
                $processedStopIds[] = $stopId;

                $formattedData = [
                    'stop_id' => $stopId,
                    'stop_code' => $stopData['stop_code'] ?: null,
                    'stop_name' => $stopData['stop_name'],
                    'stop_lon' => (float)$stopData['stop_lon'],
                    'stop_lat' => (float)$stopData['stop_lat'],
                    'stop_timezone' => $stopData['stop_timezone'] ?: null,
                    'location_type' => (int)($stopData['location_type'] ?? 0),
                    'platform_code' => isset($stopData['platform_code']) && $stopData['platform_code'] !== '' ? $stopData['platform_code'] : null,
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
        if ($hours > 23) {
            $hours = $hours % 24;
            $parts[0] = str_pad($hours, 2, '0', STR_PAD_LEFT);
            $time = implode(':', $parts);
        }
        
        return $time;
    }

    private function processBatch($batch, $type)
    {
        try {
            switch ($type) {
                case 'trips':
                    foreach ($batch as $data) {
                        $existingTrip = GtfsTrip::where('trip_id', $data['trip_id'])->first();
                        if (!$existingTrip) {
                            GtfsTrip::create($data);
                        } else {
                            $existingTrip->update($data);
                        }
                    }
                    break;
                case 'routes':
                    foreach ($batch as $data) {
                        $existingRoute = GtfsRoute::where('route_id', $data['route_id'])->first();
                        if (!$existingRoute) {
                            GtfsRoute::create($data);
                        } else {
                            $existingRoute->update($data);
                        }
                    }
                    break;
                case 'stops':
                    foreach ($batch as $data) {
                        $existingStop = GtfsStop::where('stop_id', $data['stop_id'])->first();
                        if (!$existingStop) {
                            GtfsStop::create($data);
                        } else {
                            $existingStop->update($data);
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error processing batch for ' . $type, [
                'error' => $e->getMessage(),
                'batch_size' => count($batch),
                'first_item' => $batch[0] ?? null
            ]);
            throw $e;
        }
    }
} 