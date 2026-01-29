<?php

namespace App\Console\Commands;

use App\Events\RealtimeConflictDetected;
use App\Models\GtfsSetting;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Models\RealtimeConflict;
use App\Models\StopStatus;
use App\Services\GtfsRealtime\ProtobufToArrayConverter;
use App\Services\LogHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

// Schedule GTFS Realtime updates every minute
Schedule::command(FetchGtfsRealtime::class)->everyMinute()->withoutOverlapping()->runInBackground()->onFailure(function () {
    \Log::error('GTFS Realtime command failed');
});

class FetchGtfsRealtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gtfs:fetch-realtime {--force : Force fetch regardless of update interval}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and process GTFS Realtime data to update train information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting GTFS Realtime fetch...');

        try {
            // Get GTFS settings
            $settings = GtfsSetting::first();

            if (! $settings) {
                $this->warn('No GTFS settings configured. Skipping...');

                return Command::SUCCESS;
            }

            // Determine which source to use
            $source = $settings->realtime_source ?? 'primary';
            $realtimeUrl = null;
            $updateInterval = 30;

            if ($source === 'primary') {
                $realtimeUrl = $settings->realtime_url;
                $updateInterval = $settings->realtime_update_interval ?? 30;
            } else {
                $realtimeUrl = $settings->secondary_realtime_url;
                $updateInterval = $settings->secondary_realtime_update_interval ?? 30;
            }

            if (! $realtimeUrl) {
                $this->warn("No GTFS realtime URL configured for {$source} source. Skipping...");

                return Command::SUCCESS;
            }

            // Check if enough time has passed since last update (skip when --force)
            if (! $this->option('force') && $settings->last_realtime_update) {
                $secondsSinceLastUpdate = now()->diffInSeconds($settings->last_realtime_update);
                if ($secondsSinceLastUpdate < $updateInterval) {
                    $this->info("Skipping fetch - only {$secondsSinceLastUpdate} seconds since last update (interval: {$updateInterval}s)");

                    return Command::SUCCESS;
                }
            }

            // Update last fetch attempt
            $settings->update([
                'last_realtime_update' => now(),
                'realtime_status' => 'fetching',
            ]);

            $this->info("Fetching from {$source} source: {$realtimeUrl}");

            $userAgent = config('app.name').'/1.0 (GTFS Realtime client)';
            $response = Http::timeout(30)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->withHeaders(['User-Agent' => $userAgent])
                ->get($realtimeUrl);

            if (! $response->successful()) {
                throw new \Exception("HTTP error: {$response->status()}");
            }

            $body = $response->body();
            $contentType = $response->header('Content-Type') ?? '';
            $realtimeData = $this->parseRealtimeResponse($body, $contentType, $realtimeUrl);

            if (! $realtimeData || ! isset($realtimeData['entity'])) {
                throw new \Exception('Invalid realtime data format');
            }

            $this->info('Fetched realtime data with '.count($realtimeData['entity']).' entities');

            // Process the realtime data
            $processedCount = $this->processRealtimeData($realtimeData['entity']);

            // Update status
            $settings->update([
                'realtime_status' => 'success',
                'last_realtime_update' => now(),
            ]);

            $this->info("Successfully processed {$processedCount} trip updates from {$source} source");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            LogHelper::gtfsError('GTFS Realtime fetch failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update status to error
            if (isset($settings)) {
                $settings->update([
                    'realtime_status' => 'error',
                    'last_realtime_update' => now(),
                ]);
            }

            $this->error("Failed to fetch realtime data: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Parse response body as JSON or Protobuf into canonical ['entity' => [...]] shape.
     */
    private function parseRealtimeResponse(string $body, string $contentType, string $url): ?array
    {
        $isJson = str_contains($contentType, 'application/json');
        $path = parse_url($url, PHP_URL_PATH);
        $isProtobuf = $contentType === 'application/x-protobuf'
            || ($contentType === 'application/octet-stream' && is_string($path) && str_ends_with($path, '.pb'));

        if ($isJson) {
            $decoded = json_decode($body, true);

            return is_array($decoded) ? $decoded : null;
        }

        if ($isProtobuf) {
            return app(ProtobufToArrayConverter::class)->convert($body);
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['entity'])) {
            return $decoded;
        }

        try {
            return app(ProtobufToArrayConverter::class)->convert($body);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Process the realtime data entities
     */
    private function processRealtimeData(array $entities): int
    {
        $processedCount = 0;

        foreach ($entities as $entity) {
            if (! isset($entity['trip_update'])) {
                continue;
            }

            $tripUpdate = $entity['trip_update'];
            $feedTripId = $tripUpdate['trip']['trip_id'] ?? null;

            if (! $feedTripId) {
                continue;
            }

            // Resolve to DB trip_id (feed may use different format than static GTFS)
            $tripId = $this->resolveTripId($feedTripId, $tripUpdate['trip']['start_date'] ?? null);
            if (! $tripId) {
                continue;
            }

            $trip = GtfsTrip::where('trip_id', $tripId)->first();
            if (! $trip) {
                continue;
            }

            // Handle cancellations
            if (($tripUpdate['trip']['schedule_relationship'] ?? '') === 'CANCELED') {
                $this->handleCancellation($tripId);
                $processedCount++;

                continue;
            }

            // Process stop time updates
            if (isset($tripUpdate['stop_time_update']) && is_array($tripUpdate['stop_time_update'])) {
                $this->processStopTimeUpdates($tripId, $tripUpdate['stop_time_update']);
                $processedCount++;
            }
        }

        return $processedCount;
    }

    /**
     * Resolve feed trip_id to DB trip_id (static GTFS may use different trip_id format).
     */
    private function resolveTripId(string $feedTripId, ?string $startDate): ?string
    {
        if (GtfsTrip::where('trip_id', $feedTripId)->exists()) {
            return $feedTripId;
        }

        $tripShortName = str_contains($feedTripId, '-')
            ? explode('-', $feedTripId)[0]
            : $feedTripId;

        if (! $startDate || strlen($startDate) !== 8) {
            return null;
        }

        $date = Carbon::createFromFormat('Ymd', $startDate)->format('Y-m-d');

        $trip = GtfsTrip::where('trip_short_name', $tripShortName)
            ->whereExists(function ($query) use ($date) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', $date)
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->first();

        return $trip?->trip_id;
    }

    /**
     * Process stop time updates for a trip
     */
    private function processStopTimeUpdates(string $tripId, array $stopTimeUpdates): void
    {
        foreach ($stopTimeUpdates as $stopTimeUpdate) {
            $stopSequence = $stopTimeUpdate['stop_sequence'] ?? null;

            if (! $stopSequence) {
                continue;
            }

            // Find the corresponding stop in our database
            $stopTime = GtfsStopTime::where('trip_id', $tripId)
                ->where('stop_sequence', $stopSequence)
                ->first();

            if (! $stopTime) {
                continue;
            }

            // Process platform assignment
            if (isset($stopTimeUpdate['stop_time_properties']['assigned_stop_id'])) {
                $assignedStopId = $stopTimeUpdate['stop_time_properties']['assigned_stop_id'];
                $this->updatePlatformAssignment($tripId, $stopTime->stop_id, $assignedStopId);
            }

            // Process delays
            $this->processDelays($tripId, $stopTime, $stopTimeUpdate);
        }
    }

    /**
     * Update platform assignment for a stop
     */
    private function updatePlatformAssignment(string $tripId, string $stopId, string $assignedStopId): void
    {
        // Extract platform code from assigned_stop_id (e.g., "st_pancras_international_9" -> "9")
        $platformCode = $this->extractPlatformCode($assignedStopId);

        if ($platformCode) {
            // Check if there's a manual change to the platform
            $stopStatus = StopStatus::where('trip_id', $tripId)
                ->where('stop_id', $stopId)
                ->first();

            // Check if platform was manually changed and differs from realtime value
            if ($stopStatus && $stopStatus->is_manual_change) {
                $manualPlatform = $stopStatus->departure_platform ?? $stopStatus->arrival_platform;
                if ($manualPlatform && $manualPlatform !== $platformCode && $manualPlatform !== 'TBD') {
                    // Create conflict
                    $conflict = RealtimeConflict::create([
                        'trip_id' => $tripId,
                        'stop_id' => $stopId,
                        'field_type' => 'platform',
                        'manual_value' => $manualPlatform,
                        'realtime_value' => $platformCode,
                        'manual_user_id' => $stopStatus->manually_changed_by,
                    ]);

                    // Broadcast conflict detected event
                    event(new RealtimeConflictDetected(
                        $tripId,
                        $stopId,
                        'platform',
                        $manualPlatform,
                        $platformCode,
                        $conflict->id,
                        $stopStatus->manually_changed_by
                    ));

                    return; // Don't update, wait for user resolution
                }
            }

            // No conflict, proceed with update
            StopStatus::updateOrCreate(
                [
                    'trip_id' => $tripId,
                    'stop_id' => $stopId,
                ],
                [
                    'departure_platform' => $platformCode,
                    'arrival_platform' => $platformCode,
                    'is_realtime_update' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Process delays for a stop
     */
    private function processDelays(string $tripId, GtfsStopTime $stopTime, array $stopTimeUpdate): void
    {
        $hasUpdates = false;
        $newDepartureTime = null;
        $newArrivalTime = null;

        // Process departure delay
        if (isset($stopTimeUpdate['departure']['delay'])) {
            $delaySeconds = (int) $stopTimeUpdate['departure']['delay'];
            if ($delaySeconds !== 0) {
                $newDepartureTime = $this->calculateNewTime($stopTime->departure_time, $delaySeconds);
                $hasUpdates = true;
            }
        }

        // Process arrival delay
        if (isset($stopTimeUpdate['arrival']['delay'])) {
            $delaySeconds = (int) $stopTimeUpdate['arrival']['delay'];
            if ($delaySeconds !== 0) {
                $newArrivalTime = $this->calculateNewTime($stopTime->arrival_time, $delaySeconds);
                $hasUpdates = true;
            }
        }

        if ($hasUpdates) {
            // Check for manual changes before updating departure time
            if ($newDepartureTime) {
                // Get current stop time data to check for manual changes
                $currentStopTime = DB::table('gtfs_stop_times')
                    ->where('trip_id', $stopTime->trip_id)
                    ->where('stop_sequence', $stopTime->stop_sequence)
                    ->first();

                // Check if there's a manual change and it differs from realtime value
                if ($currentStopTime && $currentStopTime->is_manual_change) {
                    $manualValue = $currentStopTime->new_departure_time ?? $currentStopTime->departure_time;
                    if ($manualValue && $manualValue !== $newDepartureTime) {
                        // Create conflict
                        $conflict = RealtimeConflict::create([
                            'trip_id' => $tripId,
                            'stop_id' => $stopTime->stop_id,
                            'field_type' => 'departure_time',
                            'manual_value' => $manualValue,
                            'realtime_value' => $newDepartureTime,
                            'manual_user_id' => $currentStopTime->manually_changed_by,
                        ]);

                        // Broadcast conflict detected event
                        event(new RealtimeConflictDetected(
                            $tripId,
                            $stopTime->stop_id,
                            'departure_time',
                            $manualValue,
                            $newDepartureTime,
                            $conflict->id,
                            $currentStopTime->manually_changed_by
                        ));

                        // Don't update departure time, wait for user resolution
                        $newDepartureTime = null;
                    }
                }

                // Update the stop time with new departure time if no conflict
                if ($newDepartureTime) {
                    DB::table('gtfs_stop_times')
                        ->where('trip_id', $stopTime->trip_id)
                        ->where('stop_sequence', $stopTime->stop_sequence)
                        ->update(['new_departure_time' => $newDepartureTime]);
                }
            }

            // Update stop status with delay information (only if we updated the time)
            if ($newDepartureTime || $newArrivalTime) {
                StopStatus::updateOrCreate(
                    [
                        'trip_id' => $tripId,
                        'stop_id' => $stopTime->stop_id,
                    ],
                    [
                        'is_realtime_update' => true,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Calculate new time by adding delay to scheduled time
     */
    private function calculateNewTime(string $scheduledTime, int $delaySeconds): string
    {
        try {
            $scheduled = Carbon::createFromFormat('H:i:s', $scheduledTime);
            $newTime = $scheduled->addSeconds($delaySeconds);

            return $newTime->format('H:i:s');
        } catch (\Exception $e) {
            LogHelper::gtfsDebug('Failed to calculate new time', [
                'scheduled_time' => $scheduledTime,
                'delay_seconds' => $delaySeconds,
                'error' => $e->getMessage(),
            ]);

            return $scheduledTime;
        }
    }

    /**
     * Extract platform code from assigned stop ID
     */
    private function extractPlatformCode(string $assignedStopId): ?string
    {
        // Extract platform from patterns like "st_pancras_international_9" -> "9"
        if (preg_match('/_(\d+[a-z]?)$/', $assignedStopId, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Handle trip cancellation
     */
    private function handleCancellation(string $tripId): void
    {
        // Check for manual status changes before updating to cancelled
        $stopStatusesWithManualChange = StopStatus::where('trip_id', $tripId)
            ->where('is_manual_change', true)
            ->get();

        foreach ($stopStatusesWithManualChange as $stopStatus) {
            // If status was manually changed and it's not already cancelled, create conflict
            if ($stopStatus->status !== 'cancelled') {
                $conflict = RealtimeConflict::create([
                    'trip_id' => $tripId,
                    'stop_id' => $stopStatus->stop_id,
                    'field_type' => 'status',
                    'manual_value' => $stopStatus->status,
                    'realtime_value' => 'cancelled',
                    'manual_user_id' => $stopStatus->manually_changed_by,
                ]);

                // Broadcast conflict detected event
                event(new RealtimeConflictDetected(
                    $tripId,
                    $stopStatus->stop_id,
                    'status',
                    $stopStatus->status,
                    'cancelled',
                    $conflict->id,
                    $stopStatus->manually_changed_by
                ));
            }
        }

        // Ensure every stop on the trip has a cancelled status (create rows if none exist)
        $stopTimes = GtfsStopTime::where('trip_id', $tripId)
            ->orderBy('stop_sequence')
            ->get();

        foreach ($stopTimes as $stopTime) {
            $existing = StopStatus::where('trip_id', $tripId)
                ->where('stop_id', $stopTime->stop_id)
                ->first();

            // Skip stops that were manually changed and are not already cancelled (conflict created above)
            if ($existing && $existing->is_manual_change && $existing->status !== 'cancelled') {
                continue;
            }

            StopStatus::updateOrCreate(
                [
                    'trip_id' => $tripId,
                    'stop_id' => $stopTime->stop_id,
                ],
                [
                    'status' => 'cancelled',
                    'is_realtime_update' => true,
                    'is_manual_change' => false,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
