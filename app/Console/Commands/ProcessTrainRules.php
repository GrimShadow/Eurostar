<?php

namespace App\Console\Commands;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\TrainStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Events\TrainAnnouncement;
use App\Models\AviavoxSetting;
use App\Livewire\CreateAnnouncement;

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
                    $train,
                    $rule
                );

                if ($conditionMet) {
                    if ($rule->action === 'set_status') {
                        // Apply the status change
                        TrainStatus::updateOrCreate(
                            ['trip_id' => $train->trip_id],
                            ['status' => $rule->status->status]
                        );

                        $this->info("Applied status {$rule->status->status} to train {$train->trip_id}");
                    } elseif ($rule->action === 'make_announcement') {
                        $settings = AviavoxSetting::first();
                        if (!$settings) {
                            $this->error('Aviavox settings not configured');
                            continue;
                        }

                        $announcement = new CreateAnnouncement();
                        $announcement->selectedAnnouncement = $rule->action_value;
                        $announcement->selectedTrain = $train->trip_headsign;
                        
                        try {
                            $xml = $announcement->generateXml();
                            $announcement->authenticateAndSendMessage(
                                $settings->ip_address,
                                $settings->port,
                                $settings->username,
                                $settings->password,
                                $xml
                            );
                            
                            $this->info("Made announcement for train {$train->trip_id}");
                        } catch (\Exception $e) {
                            $this->error("Failed to make announcement: {$e->getMessage()}");
                        }
                    }
                }
            }
        }
    }

    private function evaluateCondition($train, $rule)
    {
        $value = match ($rule->condition_type) {
            'time_until_departure' => now()->diffInMinutes($train->departure_time, false),
            'time_since_arrival' => now()->diffInMinutes($train->arrival_time),
            'platform_change' => $train->platform_changed,
            'delay_duration' => $train->delay_minutes,
            'current_status' => $train->status_id,
            'time_of_day' => now()->format('H:i'),
            default => null,
        };

        if ($value === null) return false;

        return match($rule->operator) {
            '>' => $value > $rule->value,
            '<' => $value < $rule->value,
            '=' => $value == $rule->value,
            default => false,
        };
    }
} 