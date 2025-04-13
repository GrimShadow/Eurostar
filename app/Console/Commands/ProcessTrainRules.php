<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
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
            ($rule->condition_type === 'current_status' ? $rule->conditionStatus->status : $rule->value));

        // Get all trains without status restriction
        $trains = GtfsTrip::with('status')->get();

        $this->info("Found " . $trains->count() . " trains");

        foreach ($trains as $train) {
            $this->info("\nChecking train {$train->trip_id}:");
            $this->info("Current status: " . ($train->status ? $train->status->status : 'No status'));
            
            $conditionMet = $this->evaluateCondition($rule, $train);
            
            $this->info("Condition " . ($conditionMet ? 'MET ✓' : 'NOT MET ✗'));

            if ($conditionMet) {
                $this->info("Applying action: {$rule->action}");
                $this->applyAction($rule, $train);
            }
        }
    }

    private function evaluateCondition($rule, $train)
    {
        $value = match ($rule->condition_type) {
            'time_until_departure' => $this->getMinutesUntilDeparture($train),
            'time_since_arrival' => $this->getMinutesSinceArrival($train),
            'platform_change' => $train->platform_changed,
            'delay_duration' => $train->delay_minutes,
            'current_status' => $train->status_id ?? null,  // Changed to status_id
            'time_of_day' => now()->format('H:i'),
            default => null,
        };

        

        return match($rule->operator) {
            '=' => $value == $rule->value,
            '>' => $value > $rule->value,
            '<' => $value < $rule->value,
            default => false,
        };
    }

    private function applyAction($rule, $train)
    {
        if ($rule->action === 'set_status') {
            $train->update(['status_id' => $rule->action_value]);
            $this->info("Set status for train {$train->trip_id} to {$rule->status->status}");
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