<?php

namespace App\Jobs;

use App\Models\GtfsCalendarDate;
use App\Models\GtfsRoute;
use App\Models\GtfsSetting;
use App\Models\GtfsStop;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Services\LogHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

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
            'download_status' => $status,
        ]);
    }

    public function handle()
    {
        try {
            $gtfsSettings = GtfsSetting::first();

            if (! $gtfsSettings) {
                throw new \Exception('GTFS settings not found');
            }

            $this->updateProgress($gtfsSettings, 5, 'Downloading GTFS file...');

            // Download the file
            $response = Http::timeout(300)->get($gtfsSettings->url);
            if (! $response->successful()) {
                throw new \Exception('Failed to download GTFS data: '.$response->status());
            }

            // Save the file
            $zipPath = storage_path('app/gtfs.zip');
            file_put_contents($zipPath, $response->body());

            $this->updateProgress($gtfsSettings, 10, 'Extracting GTFS files...');

            // Extract the file
            $extractPath = storage_path('app/gtfs');
            if (! file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Failed to open GTFS zip file');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Process each file with proper progress tracking
            $this->processFile($extractPath, 'stops.txt', 'syncStopsData', 15, 30);
            $this->processFile($extractPath, 'routes.txt', 'syncRoutesData', 30, 45);
            $this->processFile($extractPath, 'trips.txt', 'syncTripsData', 45, 70);
            $this->processFile($extractPath, 'stop_times.txt', 'syncStopTimesData', 70, 90);
            $this->processFile($extractPath, 'calendar_dates.txt', 'syncCalendarDatesData', 90, 95);

            $this->updateProgress($gtfsSettings, 100, 'GTFS download completed successfully');

            // Update settings
            $gtfsSettings->last_download = now();
            $gtfsSettings->is_downloading = false;
            $gtfsSettings->download_progress = 100;
            $gtfsSettings->next_download = now()->addDay()->setTime(3, 0, 0);
            $gtfsSettings->save();

            // Clean up
            unlink($zipPath);
            $this->removeDirectory($extractPath);

        } catch (\Exception $e) {
            LogHelper::gtfsError('GTFS download failed: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Update settings to reflect failure
            if (isset($gtfsSettings)) {
                $gtfsSettings->is_downloading = false;
                $gtfsSettings->download_progress = 0;
                $gtfsSettings->download_status = 'Failed: '.$e->getMessage();
                $gtfsSettings->save();
            }

            // Rethrow the exception to trigger a retry
            throw $e;
        }
    }

    private function processFile($extractPath, $filename, $method, $progressStart, $progressEnd)
    {
        try {
            $gtfsSettings = GtfsSetting::first();
            $this->updateProgress($gtfsSettings, $progressStart, "Processing {$filename}...");

            $controller = new \App\Http\Controllers\GtfsController;
            $controller->$method($extractPath);

            $this->updateProgress($gtfsSettings, $progressEnd, "Completed {$filename}");
        } catch (\Exception $e) {
            LogHelper::gtfsError("Failed to process {$filename}: ".$e->getMessage());
            throw $e;
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

    private function syncTripsData($extractPath)
    {
        $tripsFile = $extractPath.'/trips.txt';
        if (! file_exists($tripsFile)) {
            throw new \Exception('trips.txt not found in GTFS data');
        }

        Log::info('Starting GTFS trips sync');
        Log::info('Trips file path: '.$tripsFile);
        Log::info('File permissions: '.substr(sprintf('%o', fileperms($tripsFile)), -4));

        $file = fopen($tripsFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open trips.txt. Error: '.error_get_last()['message']);
        }

        $headers = fgetcsv($file);
        if (! $headers) {
            throw new \Exception('Unable to read trips.txt headers. Error: '.error_get_last()['message']);
        }

        Log::info('Trips headers: '.implode(', ', $headers));

        // Get total lines for progress calculation
        $totalLines = 0;
        while (fgets($file) !== false) {
            $totalLines++;
        }
        rewind($file);
        fgetcsv($file); // Skip headers

        DB::beginTransaction();
        try {
            // Delete all existing trips first
            GtfsTrip::query()->delete();

            $batchSize = 1000;
            $batch = [];
            $currentLine = 0;
            $created = 0;

            while (($data = fgetcsv($file)) !== false) {
                $currentLine++;
                $progress = 20 + (($currentLine / $totalLines) * 20); // 20-40% range
                $settings = GtfsSetting::first();
                $this->updateProgress($settings, $progress, "Processing trips: {$currentLine}/{$totalLines}");

                try {
                    $tripData = array_combine($headers, $data);
                    $tripId = $tripData['trip_id'];

                    $formattedData = [
                        'route_id' => $tripData['route_id'],
                        'service_id' => $tripData['service_id'],
                        'trip_id' => $tripId,
                        'trip_headsign' => $tripData['trip_headsign'],
                        'trip_short_name' => $tripData['trip_short_name'],
                        'direction_id' => (int) $tripData['direction_id'],
                        'shape_id' => $tripData['shape_id'],
                        'wheelchair_accessible' => (bool) $tripData['wheelchair_accessible'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $batch[] = $formattedData;

                    if (count($batch) >= $batchSize) {
                        GtfsTrip::insert($batch);
                        $created += count($batch);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing trip line '.$currentLine, [
                        'error' => $e->getMessage(),
                        'data' => $data,
                        'headers' => $headers,
                    ]);
                    throw $e;
                }
            }

            if (! empty($batch)) {
                GtfsTrip::insert($batch);
                $created += count($batch);
            }

            DB::commit();

            Log::info('GTFS trips sync completed', [
                'total_lines' => $totalLines,
                'created' => $created,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GTFS trips sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function syncCalendarDatesData($extractPath)
    {
        $calendarDatesFile = $extractPath.'/calendar_dates.txt';
        if (! file_exists($calendarDatesFile)) {
            throw new \Exception('calendar_dates.txt not found in GTFS data');
        }

        Log::info('Starting GTFS calendar dates sync');
        Log::info('Calendar dates file path: '.$calendarDatesFile);
        Log::info('File permissions: '.substr(sprintf('%o', fileperms($calendarDatesFile)), -4));

        $file = fopen($calendarDatesFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open calendar_dates.txt. Error: '.error_get_last()['message']);
        }

        try {
            $headers = fgetcsv($file);
            if (! $headers) {
                throw new \Exception('Unable to read calendar_dates.txt headers. Error: '.error_get_last()['message']);
            }

            Log::info('Calendar dates headers: '.implode(', ', $headers));

            DB::beginTransaction();

            // Use delete instead of truncate to avoid implicit commit
            GtfsCalendarDate::query()->delete();

            $created = 0;
            $batch = [];
            $batchSize = 1000;

            while (($data = fgetcsv($file)) !== false) {
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
                        'exception_type' => (int) $calendarData['exception_type'],
                        'created_at' => now(),
                        'updated_at' => now(),
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
                        'headers' => $headers,
                    ]);
                    throw $e;
                }
            }

            // Insert any remaining records
            if (! empty($batch)) {
                GtfsCalendarDate::insert($batch);
            }

            DB::commit();

            Log::info('GTFS calendar dates sync completed', [
                'created' => $created,
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('GTFS calendar dates sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function syncRoutesData($extractPath)
    {
        $routesFile = $extractPath.'/routes.txt';
        if (! file_exists($routesFile)) {
            throw new \Exception('routes.txt not found in GTFS data');
        }

        Log::info('Starting GTFS routes sync');
        Log::info('Routes file path: '.$routesFile);
        Log::info('File permissions: '.substr(sprintf('%o', fileperms($routesFile)), -4));

        $file = fopen($routesFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open routes.txt. Error: '.error_get_last()['message']);
        }

        $headers = fgetcsv($file);
        if (! $headers) {
            throw new \Exception('Unable to read routes.txt headers. Error: '.error_get_last()['message']);
        }

        Log::info('Routes headers: '.implode(', ', $headers));

        DB::beginTransaction();
        try {
            // Delete all existing routes first
            GtfsRoute::query()->delete();

            $batchSize = 1000;
            $batch = [];
            $created = 0;

            while (($data = fgetcsv($file)) !== false) {
                try {
                    $routeData = array_combine($headers, $data);
                    $routeId = $routeData['route_id'];

                    $formattedData = [
                        'route_id' => $routeId,
                        'agency_id' => $routeData['agency_id'],
                        'route_short_name' => $routeData['route_short_name'],
                        'route_long_name' => $routeData['route_long_name'],
                        'route_type' => (int) $routeData['route_type'],
                        'route_color' => $routeData['route_color'] ?? null,
                        'route_text_color' => $routeData['route_text_color'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $batch[] = $formattedData;

                    if (count($batch) >= $batchSize) {
                        GtfsRoute::insert($batch);
                        $created += count($batch);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing route line', [
                        'error' => $e->getMessage(),
                        'data' => $data,
                        'headers' => $headers,
                    ]);
                    throw $e;
                }
            }

            if (! empty($batch)) {
                GtfsRoute::insert($batch);
                $created += count($batch);
            }

            DB::commit();

            Log::info('GTFS routes sync completed', [
                'created' => $created,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GTFS routes sync failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            fclose($file);
        }
    }

    private function syncStopTimesData($extractPath)
    {
        $stopTimesFile = $extractPath.'/stop_times.txt';
        if (! file_exists($stopTimesFile)) {
            throw new \Exception('stop_times.txt not found in GTFS data');
        }

        Log::info('Starting GTFS stop times sync');

        $file = fopen($stopTimesFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open stop_times.txt');
        }

        $headers = fgetcsv($file);
        if (! $headers) {
            throw new \Exception('Unable to read stop_times.txt headers');
        }

        DB::beginTransaction();
        try {
            // Clear all existing records first (more efficient for stop times)
            GtfsStopTime::query()->delete();
            $created = 0;
            $batchSize = 1000;
            $batch = [];

            while (($data = fgetcsv($file)) !== false) {
                $stopTimeData = array_combine($headers, $data);

                $formattedData = [
                    'trip_id' => $stopTimeData['trip_id'],
                    'arrival_time' => $this->formatTime($stopTimeData['arrival_time']),
                    'departure_time' => $this->formatTime($stopTimeData['departure_time']),
                    'stop_id' => $stopTimeData['stop_id'],
                    'stop_sequence' => (int) $stopTimeData['stop_sequence'],
                    'drop_off_type' => (int) ($stopTimeData['drop_off_type'] ?? 0),
                    'pickup_type' => (int) ($stopTimeData['pickup_type'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $batch[] = $formattedData;
                $created++;

                if (count($batch) >= $batchSize) {
                    GtfsStopTime::insert($batch);
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                GtfsStopTime::insert($batch);
            }

            DB::commit();
            Log::info('GTFS stop times sync completed', [
                'created' => $created,
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
        $stopsFile = $extractPath.'/stops.txt';
        if (! file_exists($stopsFile)) {
            throw new \Exception('stops.txt not found in GTFS data');
        }

        Log::info('Starting GTFS stops sync');

        $file = fopen($stopsFile, 'r');
        if (! $file) {
            throw new \Exception('Unable to open stops.txt');
        }

        $headers = fgetcsv($file);
        if (! $headers) {
            throw new \Exception('Unable to read stops.txt headers');
        }

        DB::beginTransaction();
        try {
            // Delete all existing stops first
            GtfsStop::query()->delete();

            $created = 0;
            $batch = [];
            $batchSize = 1000;

            while (($data = fgetcsv($file)) !== false) {
                $stopData = array_combine($headers, $data);
                $stopId = $stopData['stop_id'];

                $formattedData = [
                    'stop_id' => $stopId,
                    'stop_code' => $stopData['stop_code'] ?: null,
                    'stop_name' => $stopData['stop_name'],
                    'stop_lon' => (float) $stopData['stop_lon'],
                    'stop_lat' => (float) $stopData['stop_lat'],
                    'stop_timezone' => $stopData['stop_timezone'] ?: null,
                    'location_type' => (int) ($stopData['location_type'] ?? 0),
                    'platform_code' => isset($stopData['platform_code']) && $stopData['platform_code'] !== '' ? $stopData['platform_code'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $batch[] = $formattedData;
                $created++;

                if (count($batch) >= $batchSize) {
                    GtfsStop::insert($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (! empty($batch)) {
                GtfsStop::insert($batch);
            }

            DB::commit();

            Log::info('GTFS stops sync completed', [
                'created' => $created,
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
        if ($hours > 23) {
            $hours = $hours % 24;
            $parts[0] = str_pad($hours, 2, '0', STR_PAD_LEFT);
            $time = implode(':', $parts);
        }

        return $time;
    }
}
