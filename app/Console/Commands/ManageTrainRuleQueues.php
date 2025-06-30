<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ManageTrainRuleQueues extends Command
{
    protected $signature = 'trains:queue {action : start|stop|status|restart}';
    protected $description = 'Manage train rule queue workers';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'start':
                $this->startWorkers();
                break;
            case 'stop':
                $this->stopWorkers();
                break;
            case 'status':
                $this->showStatus();
                break;
            case 'restart':
                $this->restartWorkers();
                break;
            default:
                $this->error('Invalid action. Use: start, stop, status, or restart');
                return 1;
        }
    }

    private function startWorkers()
    {
        $this->info('Starting train rule queue workers...');
        $this->info('Run this command in a separate terminal or use a process manager like Supervisor:');
        $this->line('');
        $this->line('php artisan queue:work --queue=train-rules --sleep=3 --tries=3 --max-time=3600');
        $this->line('');
        $this->info('For production, consider running multiple workers:');
        $this->line('php artisan queue:work --queue=train-rules --sleep=3 --tries=3 --max-time=3600 &');
        $this->line('php artisan queue:work --queue=train-rules --sleep=3 --tries=3 --max-time=3600 &');
    }

    private function stopWorkers()
    {
        $this->info('To stop queue workers, use:');
        $this->line('php artisan queue:restart');
        $this->line('');
        $this->info('Or if using Supervisor, restart the configuration.');
    }

    private function showStatus()
    {
        $this->info('Checking queue status...');
        
        // Show pending jobs
        $pendingJobs = DB::table('jobs')->where('queue', 'train-rules')->count();
        $this->line("Pending train rule jobs: {$pendingJobs}");
        
        // Show failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->line("Failed jobs (all queues): {$failedJobs}");
        
        if ($failedJobs > 0) {
            $this->warn('You have failed jobs. Check them with: php artisan queue:failed');
        }
    }

    private function restartWorkers()
    {
        $this->info('Restarting queue workers...');
        Artisan::call('queue:restart');
        $this->line(Artisan::output());
        $this->info('Workers will stop after finishing current jobs and need to be restarted manually.');
    }
} 