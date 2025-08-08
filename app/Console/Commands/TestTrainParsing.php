<?php

namespace App\Console\Commands;

use App\Models\GtfsTrip;
use Illuminate\Console\Command;

class TestTrainParsing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:train-parsing {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the train number and date parsing from GTFS trip data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Testing train parsing with limit: {$limit}");
        $this->newLine();

        $trips = GtfsTrip::limit($limit)->get();

        if ($trips->isEmpty()) {
            $this->error('No trips found in the database.');
            return 1;
        }

        $this->table(
            ['Trip ID', 'Service ID', 'Train Number', 'Formatted Date', 'Human Date', 'Original Short Name'],
            $trips->map(function ($trip) {
                return [
                    $trip->trip_id,
                    $trip->service_id,
                    $trip->train_number,
                    $trip->formatted_date ?? 'N/A',
                    $trip->human_readable_date ?? 'N/A',
                    $trip->trip_short_name
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info('Parsing test completed successfully!');
        
        // Show some statistics
        $totalTrips = GtfsTrip::count();
        $tripsWithDates = GtfsTrip::whereNotNull('trip_id')->where('trip_id', 'like', '%-%')->count();
        $uniqueTrainNumbers = GtfsTrip::get()->pluck('train_number')->unique()->count();
        
        $this->info("Total trips in database: {$totalTrips}");
        $this->info("Trips with date format (XX-XXXX): {$tripsWithDates}");
        $this->info("Unique train numbers found: {$uniqueTrainNumbers}");

        return 0;
    }
} 