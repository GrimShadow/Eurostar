<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPlatformAssignments extends Command
{
    protected $signature = 'platforms:import';
    protected $description = 'Import platform assignments from GTFS data';

    public function handle()
    {
        $this->info('Starting platform assignments import...');

        // Get all trips for today
        $today = now()->format('Y-m-d');
        $trips = DB::table('gtfs_trips')
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->where('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->select('gtfs_trips.trip_id')
            ->get();

        $this->info("Found {$trips->count()} trips for today");

        $imported = 0;
        $skipped = 0;

        foreach ($trips as $trip) {
            // Get first and last stops for each trip
            $firstStop = DB::table('gtfs_stop_times')
                ->where('trip_id', $trip->trip_id)
                ->where('stop_sequence', 1)
                ->first();

            $lastStop = DB::table('gtfs_stop_times')
                ->where('trip_id', $trip->trip_id)
                ->whereRaw('stop_sequence = (
                    SELECT MAX(stop_sequence) 
                    FROM gtfs_stop_times 
                    WHERE trip_id = ?
                )', [$trip->trip_id])
                ->first();

            if ($firstStop && $lastStop) {
                // Check if platform assignments already exist
                $existing = DB::table('train_platform_assignments')
                    ->where('trip_id', $trip->trip_id)
                    ->whereIn('stop_id', [$firstStop->stop_id, $lastStop->stop_id])
                    ->count();

                if ($existing === 0) {
                    // Import platform assignments
                    DB::table('train_platform_assignments')->insert([
                        [
                            'trip_id' => $trip->trip_id,
                            'stop_id' => $firstStop->stop_id,
                            'platform_code' => 'TBD',
                            'created_at' => now(),
                            'updated_at' => now()
                        ],
                        [
                            'trip_id' => $trip->trip_id,
                            'stop_id' => $lastStop->stop_id,
                            'platform_code' => 'TBD',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    ]);
                    $imported += 2;
                } else {
                    $skipped += 2;
                }
            }
        }

        $this->info("Import completed:");
        $this->info("- Imported: {$imported} platform assignments");
        $this->info("- Skipped: {$skipped} platform assignments (already exist)");

        return 0;
    }
} 