<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\TrainRuleExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonitorTrainRulePerformance extends Command
{
    protected $signature = 'trains:monitor-performance {--detailed : Show detailed performance metrics}';

    protected $description = 'Monitor train rule processing performance';

    public function handle()
    {
        $this->info('=== Train Rule Performance Monitor ===');
        $this->newLine();

        // Check active rules
        $activeRules = TrainRule::where('is_active', true)->count();
        $this->info("Active Rules: {$activeRules}");

        // Check recent executions
        $recentExecutions = TrainRuleExecution::where('executed_at', '>=', now()->subMinutes(10))->count();
        $this->info("Recent Executions (last 10 min): {$recentExecutions}");

        // Check cache status
        $interval = floor(now()->minute / 5) * 5;
        $trainDataCacheKey = 'shared_train_data_'.now()->format('Y-m-d_H:').str_pad($interval, 2, '0', STR_PAD_LEFT);
        $trainDataCache = Cache::has($trainDataCacheKey);
        $this->info('Train Data Cache Active: '.($trainDataCache ? 'Yes' : 'No'));

        $rulesCache = Cache::has('active_train_rules_5min');
        $this->info('Rules Cache Active: '.($rulesCache ? 'Yes' : 'No'));

        // Check processing flag
        $processingFlag = Cache::has('train_rules_processing');
        $this->info('Processing Flag Active: '.($processingFlag ? 'Yes' : 'No'));

        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('=== Detailed Metrics ===');

            // Show slow queries
            $this->showSlowQueries();

            // Show execution statistics
            $this->showExecutionStats();

            // Show cache hit rates
            $this->showCacheStats();
        }

        $this->newLine();
        $this->info('=== Recommendations ===');
        $this->showRecommendations($activeRules, $recentExecutions);
    }

    private function showSlowQueries()
    {
        $this->info('Recent Slow Queries:');

        // This would require slow query log to be enabled
        // For now, show query statistics
        $queryStats = DB::select('
            SELECT 
                COUNT(*) as total_queries,
                AVG(TIME_TO_SEC(TIME(Query_time))) as avg_query_time
            FROM mysql.slow_log 
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');

        if (! empty($queryStats)) {
            $this->line("Total queries in last hour: {$queryStats[0]->total_queries}");
            $this->line("Average query time: {$queryStats[0]->avg_query_time}s");
        } else {
            $this->line('No slow query data available (slow query log may not be enabled)');
        }
    }

    private function showExecutionStats()
    {
        $this->info('Execution Statistics (Last 24 hours):');

        $stats = TrainRuleExecution::selectRaw('
            DATE(executed_at) as date,
            COUNT(*) as executions,
            COUNT(DISTINCT rule_id) as unique_rules,
            COUNT(DISTINCT trip_id) as unique_trains
        ')
            ->where('executed_at', '>=', now()->subDay())
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        foreach ($stats as $stat) {
            $this->line("Date: {$stat->date} | Executions: {$stat->executions} | Rules: {$stat->unique_rules} | Trains: {$stat->unique_trains}");
        }
    }

    private function showCacheStats()
    {
        $this->info('Cache Statistics:');

        // Check various cache keys
        $interval = floor(now()->minute / 5) * 5;
        $cacheKeys = [
            'shared_train_data_'.now()->format('Y-m-d_H:').str_pad($interval, 2, '0', STR_PAD_LEFT),
            'active_train_rules_5min',
            'train_rules_processing',
        ];

        foreach ($cacheKeys as $key) {
            $exists = Cache::has($key);
            $this->line("Cache key '{$key}': ".($exists ? 'EXISTS' : 'MISSING'));
        }
    }

    private function showRecommendations($activeRules, $recentExecutions)
    {
        if ($activeRules > 20) {
            $this->warn("⚠️  High number of active rules ({$activeRules}). Consider consolidating rules.");
        }

        if ($recentExecutions > 100) {
            $this->warn("⚠️  High execution rate ({$recentExecutions} in 10 min). Consider increasing processing interval.");
        }

        if ($recentExecutions === 0) {
            $this->warn('⚠️  No recent executions. Check if rules are being processed.');
        }

        $this->info('✅ Recommendations:');
        $this->line('  - Monitor MySQL CPU usage during rule processing');
        $this->line("  - Consider increasing cache duration if data doesn't change frequently");
        $this->line('  - Review rule conditions for optimization opportunities');
        $this->line("  - Use 'php artisan trains:monitor-performance --detailed' for more info");
    }
}
