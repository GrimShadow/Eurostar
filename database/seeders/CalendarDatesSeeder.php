<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CalendarDate;
use Carbon\Carbon;

class CalendarDatesSeeder extends Seeder
{
    public function run()
    {
        // Add a sample calendar date for today
        CalendarDate::create([
            'service_id' => '1', // Make sure this matches a service_id in your gtfs_trips table
            'date' => Carbon::now()->format('Y-m-d'),
            'exception_type' => 1
        ]);
    }
} 