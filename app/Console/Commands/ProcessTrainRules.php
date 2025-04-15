<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessTrainRules extends Command
{
    protected $signature = 'trains:process-rules {--debug : Show debug information} {--test : Run in test mode with shorter intervals}';
    protected $description = 'Process all active train rules and apply actions';

    public function handle()
    {
        if ($this->option('test')) {
            while (true) {
                $this->processRules();
                $this->info("\n=== Waiting 10 seconds before next check ===\n");
                sleep(10);
            }
        } else {
            $this->processRules();
        }
    }

    private function processRules()
    {
        $activeRules = TrainRule::with(['status', 'conditionStatus'])
            ->where('is_active', true)
            ->get();

        $this->info("Processing " . $activeRules->count() . " active rules");

        foreach ($activeRules as $rule) {
            $this->processRule($rule);
        }
    }

    private function processRule($rule)
    {
        $this->info("\nProcessing rule: When {$rule->condition_type} {$rule->operator} " . 
            ($rule->condition_type === 'current_status' ? $rule->conditionStatus?->status : $rule->value));

        // Get all trains without status restriction
        $trains = GtfsTrip::with('currentStatus')->get();

        $this->info("Found " . $trains->count() . " trains");

        foreach ($trains as $train) {
            $this->info("\nChecking train {$train->trip_id}:");
            $this->info("Current status: " . ($train->currentStatus ? $train->currentStatus->status : 'No status'));
            
            $conditionMet = $this->evaluateCondition($train, $rule);
            
            $this->info("Condition " . ($conditionMet ? 'MET ✓' : 'NOT MET ✗'));

            if ($conditionMet) {
                $this->info("Applying action: {$rule->action}");
                $this->applyAction($rule, $train);
            }
        }
    }

    private function evaluateCondition($trip, $rule)
    {
        switch ($rule->condition_type) {
            case 'current_status':
                $currentStatus = $trip->currentStatus;
                $status = $currentStatus ? $currentStatus->status : 'on-time';
                $ruleStatus = $rule->conditionStatus?->status;
                $this->info("Comparing current status '{$status}' with rule status '{$ruleStatus}'");
                return $status === $ruleStatus;
            
            case 'departure_time':
                $departureTime = strtotime($trip->departure_time);
                $ruleTime = strtotime($rule->value);
                return $departureTime >= $ruleTime;
            
            case 'arrival_time':
                $arrivalTime = strtotime($trip->arrival_time);
                $ruleTime = strtotime($rule->value);
                return $arrivalTime >= $ruleTime;
            
            case 'time_until_departure':
                $minutesUntilDeparture = $this->getMinutesUntilDeparture($trip);
                $this->info("Minutes until departure: {$minutesUntilDeparture}, Rule value: {$rule->value}");
                return $this->compareWithOperator($minutesUntilDeparture, $rule->operator, (int)$rule->value);
            
            case 'time_since_arrival':
                $minutesSinceArrival = $this->getMinutesSinceArrival($trip);
                $this->info("Minutes since arrival: {$minutesSinceArrival}, Rule value: {$rule->value}");
                return $this->compareWithOperator($minutesSinceArrival, $rule->operator, (int)$rule->value);
            
            default:
                $this->info("Unknown condition type: {$rule->condition_type}");
                return false;
        }
    }

    private function compareWithOperator($value, $operator, $ruleValue)
    {
        switch ($operator) {
            case '>':
                return $value > $ruleValue;
            case '<':
                return $value < $ruleValue;
            case '=':
                return $value == $ruleValue;
            default:
                return false;
        }
    }

    private function applyAction($rule, $train)
    {
        if ($rule->action === 'set_status') {
            // Get the status text from the statuses table
            $status = Status::find($rule->action_value);
            if (!$status) {
                $this->error("Status with ID {$rule->action_value} not found");
                return;
            }

            // Update or create the status in train_statuses table
            $trainStatus = TrainStatus::updateOrCreate(
                ['trip_id' => $train->trip_id],
                ['status' => $status->status]
            );
            $this->info("Set status for train {$train->trip_id} to {$status->status}");

            // Broadcast the status change event
            event(new \App\Events\TrainStatusUpdated($train->trip_id, $status->status));
        } 
        elseif ($rule->action === 'make_announcement') {
            $announcementData = json_decode($rule->action_value, true);
            $template = AviavoxTemplate::find($announcementData['template_id']);
            
            $this->makeAnnouncement($template, $announcementData, $train);
            
            $this->info("Made announcement for train {$train->trip_id} using template {$template->name}");
        }
    }

    private function getMinutesUntilDeparture($train)
    {
        $departureTime = Carbon::createFromFormat('H:i:s', $train->departure_time);
        return now()->diffInMinutes($departureTime, false);
    }

    private function getMinutesSinceArrival($train)
    {
        $arrivalTime = Carbon::createFromFormat('H:i:s', $train->arrival_time);
        return now()->diffInMinutes($arrivalTime);
    }

    private function makeAnnouncement($template, $announcementData, $train)
    {
        $this->info("🔊 Making announcement:");
        $this->info("Template: " . $template->name);
        $this->info("Zone: " . $announcementData['zone']);
        if (!empty($announcementData['variables'])) {
            $this->info("Variables: " . json_encode($announcementData['variables'], JSON_PRETTY_PRINT));
        }
        // Here you would integrate with your actual announcement system
    }
} 