<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These cron jobs are run in the background by a process manager like Supervisor or Laravel Horizon.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule GTFS Realtime updates based on configured interval
        $schedule->command('gtfs:fetch-realtime')
            ->everyThirtySeconds()
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('GTFS Realtime command failed');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        Commands\ProcessTrainRules::class,
        Commands\ImportPlatformAssignments::class,
    ];
}
