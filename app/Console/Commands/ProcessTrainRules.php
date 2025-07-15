<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Jobs\ProcessSingleTrainRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ProcessTrainRules::class)->everyMinute();

class ProcessTrainRules extends Command
{
    protected $signature = 'trains:process-rules {--debug : Show debug information}';
    protected $description = 'Enqueue train rule processing jobs for execution';

    public function handle()
    {
        // Cache active rules for 2 minutes to reduce database load
        $activeRules = \Illuminate\Support\Facades\Cache::remember('active_train_rules_2min', 120, function () {
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

        $this->info("Enqueuing {$activeRules->count()} active rules for processing");

        foreach ($activeRules as $rule) {
            // Dispatch each rule as a separate queue job
            ProcessSingleTrainRule::dispatch($rule->id)
                ->onQueue('train-rules'); // Use dedicated queue for train rules
        }

        if ($this->option('debug')) {
            $this->info("Successfully enqueued {$activeRules->count()} rule processing jobs");
        }
    }
} 