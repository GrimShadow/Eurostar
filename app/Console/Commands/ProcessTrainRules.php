<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Jobs\ProcessSingleTrainRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;

// Process rules every 3 minutes instead of every minute to reduce database load
Schedule::command(ProcessTrainRules::class)->everyThreeMinutes();

class ProcessTrainRules extends Command
{
    protected $signature = 'trains:process-rules {--debug : Show debug information}';
    protected $description = 'Enqueue train rule processing jobs for execution';

    public function handle()
    {
        // Cache active rules for 5 minutes to reduce database load
        $activeRules = \Illuminate\Support\Facades\Cache::remember('active_train_rules_5min', 300, function () {
            return TrainRule::with('conditions')
                ->where('is_active', true)
                ->get();
        });

        if ($activeRules->isEmpty()) {
            if ($this->option('debug')) {
                $this->info('No active rules found to process');
            }
            return;
        }

        // Check if we're already processing rules to prevent overlapping
        $processingKey = 'train_rules_processing';
        if (Cache::has($processingKey)) {
            if ($this->option('debug')) {
                $this->info('Train rules already being processed, skipping this run');
            }
            return;
        }

        // Set processing flag for 2 minutes
        Cache::put($processingKey, true, 120);

        try {
            $this->info("Enqueuing {$activeRules->count()} active rules for processing");

            // Process rules in smaller batches to reduce queue load
            $batches = $activeRules->chunk(5);
            
            foreach ($batches as $batch) {
                foreach ($batch as $rule) {
                    // Dispatch each rule as a separate queue job with delay to spread load
                    ProcessSingleTrainRule::dispatch($rule->id)
                        ->onQueue('train-rules')
                        ->delay(now()->addSeconds(rand(1, 10))); // Random delay to spread load
                }
            }

            if ($this->option('debug')) {
                $this->info("Successfully enqueued {$activeRules->count()} rule processing jobs");
            }
        } finally {
            // Remove processing flag
            Cache::forget($processingKey);
        }
    }
} 