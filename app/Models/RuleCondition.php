<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'train_rule_id',
        'condition_type',
        'operator',
        'value',
        'logical_operator',
        'order'
    ];

    public function rule()
    {
        return $this->belongsTo(TrainRule::class, 'train_rule_id');
    }

    public function evaluate($train, $specificStopId = null)
    {
        switch ($this->condition_type) {
            case 'time_until_departure':
                // Get the first stop's departure time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
                }
                if (!$stopTime) return false;
                
                $now = Carbon::now();
                $today = $now->format('Y-m-d');
                
                // Create departure time for today
                $departureTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $stopTime->departure_time);
                
                // Calculate minutes until departure (positive = future, negative = past)
                $minutesUntilDeparture = $now->diffInMinutes($departureTime, false);
                
                return $this->compare($minutesUntilDeparture, $this->value);
            
            case 'time_after_departure':
                // Get the first stop's departure time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
                }
                if (!$stopTime) return false;
                
                $now = Carbon::now();
                $today = $now->format('Y-m-d');
                
                // Create departure time for today
                $departureTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $stopTime->departure_time);
                
                // Calculate minutes after departure (positive = departed, negative = not yet departed)
                $minutesAfterDeparture = $departureTime->diffInMinutes($now, false);
                
                return $this->compare($minutesAfterDeparture, $this->value);
            
            case 'time_until_arrival':
                // Get the last stop's arrival time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderByDesc('stop_sequence')->first();
                }
                if (!$stopTime) return false;
                
                $now = Carbon::now();
                $today = $now->format('Y-m-d');
                
                // Create arrival time for today
                $arrivalTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $stopTime->arrival_time);
                
                // Calculate minutes until arrival (positive = future, negative = past)
                $minutesUntilArrival = $now->diffInMinutes($arrivalTime, false);
                
                return $this->compare($minutesUntilArrival, $this->value);
            
            case 'time_after_arrival':
                // Get the last stop's arrival time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderByDesc('stop_sequence')->first();
                }
                if (!$stopTime) return false;
                
                $arrivalTime = Carbon::createFromFormat('H:i:s', $stopTime->arrival_time);
                $now = Carbon::now();
                
                // Calculate minutes after arrival (positive = arrived, negative = not yet arrived)
                $minutesAfterArrival = $now->diffInMinutes($arrivalTime, false);
                
                return $this->compare($minutesAfterArrival, $this->value);
            
            case 'time_since_arrival':
                // Get the last stop's arrival time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderByDesc('stop_sequence')->first();
                }
                if (!$stopTime) return false;
                
                $arrivalTime = Carbon::createFromFormat('H:i:s', $stopTime->arrival_time);
                $minutesSinceArrival = Carbon::now()->diffInMinutes($arrivalTime, false);
                return $this->compare($minutesSinceArrival, $this->value);
            
            case 'platform_change':
                // This would need specific logic for platform changes
                return false;
            
            case 'delay_duration':
                // This would need specific logic for delay calculations
                return false;
            
            case 'current_status':
                // Check StopStatus for specific stop or first stop
                if ($specificStopId) {
                    $stopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                        ->where('stop_id', $specificStopId)
                        ->first();
                    $statusValue = $stopStatus ? $stopStatus->status : 'On Time';
                } else {
                $firstStopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
                if (!$firstStopTime) {
                    $statusValue = 'On Time';
                } else {
                    $stopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                        ->where('stop_id', $firstStopTime->stop_id)
                        ->first();
                    $statusValue = $stopStatus ? $stopStatus->status : 'On Time';
                    }
                }
                
                // If the value is numeric (status ID), get the actual status text
                if (is_numeric($this->value)) {
                    $status = \App\Models\Status::find($this->value);
                    $compareValue = $status ? $status->status : $this->value;
                } else {
                    $compareValue = $this->value;
                }
                
                // Normalize both values for comparison (case-insensitive, handle dash/space differences)
                $normalizedStatusValue = strtolower(str_replace([' ', '-'], '', $statusValue));
                $normalizedCompareValue = strtolower(str_replace([' ', '-'], '', $compareValue));
                
                // Debug logging removed - rule is working correctly
                
                return $this->compare($normalizedStatusValue, $normalizedCompareValue);
            
            case 'time_of_day':
                $currentTime = Carbon::now()->format('H:i:s');
                return $this->compare($currentTime, $this->value);
            
            case 'train_number':
                $trainNumber = $train->trip_short_name ?? $train->trip_id;
                // Extract just the train number (first part before space)
                $extractedTrainNumber = explode(' ', $trainNumber)[0];
                return $this->compare($extractedTrainNumber, $this->value);
            
            default:
                return false;
        }
    }

    private function compare($value1, $value2)
    {
        switch ($this->operator) {
            case '>':
                return $value1 > $value2;
            case '>=':
                return $value1 >= $value2;
            case '<':
                return $value1 < $value2;
            case '<=':
                return $value1 <= $value2;
            case '=':
                return $value1 == $value2;
            case '!=':
                return $value1 != $value2;
            default:
                return false;
        }
    }
} 