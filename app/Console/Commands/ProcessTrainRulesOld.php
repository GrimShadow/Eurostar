<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * OLD SYNCHRONOUS VERSION - ARCHIVED FOR REFERENCE
 * This command caused "Too many connections" errors due to:
 * - Processing all rules synchronously
 * - Nested loops through all trains for each rule
 * - Long-running database connections
 * - No connection pool management
 * 
 * Use the new ProcessTrainRules command with queue jobs instead.
 */
class ProcessTrainRulesOld extends Command
{
    protected $signature = 'trains:process-rules-old {--debug : Show debug information} {--test : Run in test mode with shorter intervals}';
    protected $description = '[DEPRECATED] Old synchronous train rules processor - use trains:process-rules instead';

    public function handle()
    {
        $this->error('This command is deprecated due to connection pooling issues.');
        $this->error('Use "php artisan trains:process-rules" instead.');
        $this->error('Make sure to run queue workers: "php artisan queue:work --queue=train-rules"');
        return 1;
    }

    // Original logic preserved for reference only...
    private function processRulesOld()
    {
        $activeRules = TrainRule::with(['status', 'conditions'])
            ->where('is_active', true)
            ->get();

        $this->info("Processing " . $activeRules->count() . " active rules");

        foreach ($activeRules as $rule) {
            $this->processRule($rule);
        }
    }

    private function processRule($rule)
    {
        $conditionsDescription = $rule->conditions->map(function($condition, $index) {
            $prefix = $index > 0 ? strtoupper($condition->logical_operator) . ' ' : '';
            return $prefix . "When {$condition->condition_type} {$condition->operator} {$condition->value}";
        })->implode(' ');
        
        $this->info("\nProcessing rule: {$conditionsDescription}");

        // This was the problem: loading ALL trains into memory at once
        $trains = GtfsTrip::with(['currentStatus', 'stopTimes'])->get();

        $this->info("Found " . $trains->count() . " trains");

        foreach ($trains as $train) {
            $this->info("\nChecking train {$train->trip_id}:");
            $this->info("Current status: " . ($train->currentStatus ? $train->currentStatus->status : 'No status'));
            
            $conditionMet = $rule->shouldTrigger($train);
            
            $this->info("Condition " . ($conditionMet ? 'MET ✓' : 'NOT MET ✗'));

            if ($conditionMet) {
                $this->info("Applying action: {$rule->action}");
                $this->applyAction($rule, $train);
            }
        }
    }

    private function applyAction($rule, $train)
    {
        if ($rule->action === 'set_status') {
            $status = Status::find($rule->action_value);
            if (!$status) {
                $this->error("Status with ID {$rule->action_value} not found");
                return;
            }

            $trainStatus = TrainStatus::updateOrCreate(
                ['trip_id' => $train->trip_id],
                ['status' => $status->status]
            );
            $this->info("Set status for train {$train->trip_id} to {$status->status}");

            event(new \App\Events\TrainStatusUpdated($train->trip_id, $status->status));
        } 
        elseif ($rule->action === 'make_announcement') {
            $announcementData = json_decode($rule->action_value, true);
            $template = AviavoxTemplate::find($announcementData['template_id']);
            
            $this->makeAnnouncement($template, $announcementData, $train);
            
            $this->info("Made announcement for train {$train->trip_id} using template {$template->name}");
        }
    }

    private function makeAnnouncement($template, $announcementData, $train)
    {
        $this->info("🔊 Making announcement:");
        $this->info("Template: " . $template->name);
        $this->info("Zone: " . $announcementData['zone']);
        if (!empty($announcementData['variables'])) {
            $this->info("Variables: " . json_encode($announcementData['variables'], JSON_PRETTY_PRINT));
        }
    }
} 