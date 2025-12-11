<?php

namespace App\Console\Commands;

use App\Models\GtfsSetting;
use App\Models\GtfsStopTime;
use App\Models\GtfsTrip;
use App\Models\StopStatus;
use App\Services\LogHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
    protected $signature = 'gtfs:fetch-realtime';

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

            if (! $settings || ! $settings->realtime_url) {
                $this->warn('No GTFS realtime URL configured. Skipping...');

                return Command::SUCCESS;
            }

            // Update last fetch attempt
            $settings->update([
                'last_realtime_update' => now(),
                'realtime_status' => 'fetching',
            ]);

            // Fetch realtime data
            $response = Http::timeout(30)->get($settings->realtime_url);

            if (! $response->successful()) {
                throw new \Exception("HTTP error: {$response->status()}");
            }

            $realtimeData = $response->json();

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

            $this->info("Successfully processed {$processedCount} trip updates");

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
            $tripId = $tripUpdate['trip']['trip_id'] ?? null;

            if (! $tripId) {
                continue;
            }

            // Check if this trip exists in our database
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
            // Update the stop time with new departure time
            // Use DB::table() instead of Eloquent update() for composite primary key
            if ($newDepartureTime) {
                \Illuminate\Support\Facades\DB::table('gtfs_stop_times')
                    ->where('trip_id', $stopTime->trip_id)
                    ->where('stop_sequence', $stopTime->stop_sequence)
                    ->update(['new_departure_time' => $newDepartureTime]);
            }

            // Update stop status with delay information
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
        // Update all stop statuses for this trip to cancelled
        StopStatus::where('trip_id', $tripId)
            ->update([
                'status' => 'cancelled',
                'is_realtime_update' => true,
                'updated_at' => now(),
            ]);
    }
}
