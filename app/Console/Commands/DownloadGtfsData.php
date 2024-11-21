<?php

namespace App\Console\Commands;

use App\Models\GtfsSetting;
use App\Http\Controllers\GtfsController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schedule;

Schedule::command(DownloadGtfsData::class)->dailyAt('04:00');

class DownloadGtfsData extends Command
{
    protected $signature = 'gtfs:download';
    protected $description = 'Download and extract GTFS data';

    public function handle()
    {
        $settings = GtfsSetting::first();
        
        if (!$settings || !$settings->is_active) {
            $this->error('GTFS settings not configured or inactive');
            return 1;
        }

        if (now()->lt($settings->next_download)) {
            $this->info('Next download scheduled for: ' . $settings->next_download);
            return 0;
        }

        $this->info('Downloading GTFS data...');
        (new GtfsController)->downloadGtfs();
        
        return 0;
    }
}