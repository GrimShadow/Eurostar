<?php

namespace App\Console\Commands;

use App\Models\TrainRuleExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CleanupTrainRuleExecutions::class)->daily();

class CleanupTrainRuleExecutions extends Command
{
    protected $signature = 'train-rules:cleanup-executions {--hours=24 : Hours to keep executions for}';
    protected $description = 'Clean up old train rule executions to prevent table bloat';

    public function handle()
    {
        $hours = $this->option('hours');
        
        $deletedCount = TrainRuleExecution::where('executed_at', '<', now()->subHours($hours))->delete();
        
        $this->info("Cleaned up {$deletedCount} old train rule executions (older than {$hours} hours)");
        
        Log::info("Train rule executions cleanup completed", [
            'deleted_count' => $deletedCount,
            'hours_threshold' => $hours
        ]);
        
        return 0;
    }
} 