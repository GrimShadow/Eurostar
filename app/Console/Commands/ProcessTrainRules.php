<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessTrainRules extends Command
{
    protected $signature = 'trains:process-rules';
    protected $description = 'Process all active train rules and apply status changes';

    public function handle()
    {
        $activeRules = TrainRule::with('status')
            ->where('is_active', true)
            ->get();

        foreach ($activeRules as $rule) {
            // Get all trains for today
            $today = Carbon::now()->format('Y-m-d');
            $trains = GtfsTrip::whereHas('calendar_date', function ($query) use ($today) {
                $query->where('date', $today)
                    ->where('exception_type', 1);
            })->get();

            foreach ($trains as $train) {
                $departureTime = Carbon::createFromFormat('H:i:s', $train->departure_time);
                $minutesUntilDeparture = now()->diffInMinutes($departureTime, false);

                // Check if the condition is met
                $conditionMet = $this->evaluateCondition(
                    $minutesUntilDeparture,
                    $rule->operator,
                    $rule->value
                );

                if ($conditionMet) {
                    // Apply the status change
                    TrainStatus::updateOrCreate(
                        ['trip_id' => $train->trip_id],
                        ['status' => $rule->status->status]
                    );

                    $this->info("Applied status {$rule->status->status} to train {$train->trip_id}");
                }
            }
        }
    }

    private function evaluateCondition($actual, $operator, $expected)
    {
        return match($operator) {
            '>' => $actual > $expected,
            '<' => $actual < $expected,
            '=' => $actual == $expected,
            default => false,
        };
    }
} 