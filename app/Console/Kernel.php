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
        // GTFS Realtime is now scheduled in FetchGtfsRealtime.php

        // Clean up expired cache entries every 15 minutes to prevent database bloat
        $schedule->command('cache:cleanup-expired')
            ->everyFifteenMinutes()
            ->onFailure(function () {
                \Log::error('Cache cleanup command failed');
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
