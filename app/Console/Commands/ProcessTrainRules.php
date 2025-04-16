<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Events\TrainStatusUpdated;
use App\Models\GtfsStopTime;

class ProcessTrainRules extends Command
{
    protected $signature = 'trains:process-rules {--debug : Show debug information} {--test : Run in test mode with shorter intervals}';
    protected $description = 'Process all active train rules and apply actions';

    public function handle()
    {
        try {
            Log::info('Starting train rules processing');
            
            // Get current date and time
            $now = Carbon::now();
            $today = $now->format('Y-m-d');
            $currentTime = $now->format('H:i:s');
            
            Log::info('Processing rules for date and time', [
                'date' => $today,
                'time' => $currentTime
            ]);

            // Get all active rules
            $rules = TrainRule::where('is_active', true)->get();
            Log::info('Found active rules', ['count' => $rules->count()]);

            foreach ($rules as $rule) {
                try {
                    Log::info('Processing rule', [
                        'rule_id' => $rule->id,
                        'name' => $rule->name
                    ]);

                    // Get trains that match the rule's criteria
                    $trains = GtfsTrip::query()
                        ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
                        ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
                        ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
                        ->whereIn('gtfs_trips.route_id', function($query) {
                            $query->select('route_id')
                                ->from('selected_routes')
                                ->where('is_active', true);
                        })
                        ->whereDate('gtfs_calendar_dates.date', $today)
                        ->where('gtfs_calendar_dates.exception_type', 1)
                        ->where('gtfs_stop_times.stop_sequence', 1)
                        ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
                        ->where('gtfs_stop_times.departure_time', '<=', date('H:i:s', strtotime($currentTime . ' +4 hours')))
                        ->select([
                            'gtfs_trips.trip_id',
                            'gtfs_trips.trip_headsign',
                            'gtfs_stop_times.departure_time',
                            'gtfs_routes.route_long_name'
                        ])
                        ->get();

                    Log::info('Found matching trains for rule', [
                        'rule_id' => $rule->id,
                        'train_count' => $trains->count()
                    ]);

                    foreach ($trains as $train) {
                        try {
                            // Check if the train matches the rule's conditions
                            if ($this->matchesRule($train, $rule)) {
                                Log::info('Train matches rule', [
                                    'rule_id' => $rule->id,
                                    'train_id' => $train->trip_id,
                                    'departure_time' => $train->departure_time
                                ]);

                                // Update train status
                                TrainStatus::updateOrCreate(
                                    ['trip_id' => $train->trip_id],
                                    ['status' => $rule->status]
                                );

                                // If the rule specifies a new departure time, update it
                                if ($rule->new_departure_time) {
                                    Log::info('Updating departure time', [
                                        'train_id' => $train->trip_id,
                                        'new_time' => $rule->new_departure_time
                                    ]);

                                    GtfsStopTime::where('trip_id', $train->trip_id)
                                        ->where('stop_sequence', 1)
                                        ->update(['departure_time' => $rule->new_departure_time]);
                                }

                                // Broadcast the status update
                                broadcast(new TrainStatusUpdated($train->trip_id, $rule->status));
                            }
                        } catch (\Exception $e) {
                            Log::error('Error processing train for rule', [
                                'rule_id' => $rule->id,
                                'train_id' => $train->trip_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing rule', [
                        'rule_id' => $rule->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Completed train rules processing');
        } catch (\Exception $e) {
            Log::error('Error in ProcessTrainRules command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error processing train rules: ' . $e->getMessage());
        }
    }

    private function matchesRule($train, $rule)
    {
        try {
            // Log the data being compared
            Log::info('Checking rule conditions', [
                'train' => [
                    'trip_headsign' => $train->trip_headsign,
                    'route_name' => $train->route_long_name,
                    'departure_time' => $train->departure_time
                ],
                'rule' => [
                    'train_number' => $rule->train_number,
                    'route_name' => $rule->route_name,
                    'departure_time' => $rule->departure_time
                ]
            ]);

            // Check if the train matches the rule's conditions
            $matches = true;

            if ($rule->train_number && $train->trip_headsign != $rule->train_number) {
                $matches = false;
            }

            if ($rule->route_name && $train->route_long_name != $rule->route_name) {
                $matches = false;
            }

            if ($rule->departure_time && $train->departure_time != $rule->departure_time) {
                $matches = false;
            }

            Log::info('Rule match result', [
                'matches' => $matches,
                'train_id' => $train->trip_id,
                'rule_id' => $rule->id
            ]);

            return $matches;
        } catch (\Exception $e) {
            Log::error('Error in matchesRule', [
                'error' => $e->getMessage(),
                'train' => $train,
                'rule' => $rule
            ]);
            return false;
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