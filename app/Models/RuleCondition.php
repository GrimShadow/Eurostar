<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function evaluate($train)
    {
        switch ($this->condition_type) {
            case 'time_until_departure':
                // Get the first stop's departure time
                $firstStop = $train->stopTimes()->orderBy('stop_sequence')->first();
                if (!$firstStop) return false;
                
                $departureTime = Carbon::createFromFormat('H:i:s', $firstStop->departure_time);
                $minutesUntilDeparture = $departureTime->diffInMinutes(Carbon::now(), false);
                return $this->compare($minutesUntilDeparture, $this->value);
            
            case 'time_since_arrival':
                // Get the last stop's arrival time  
                $lastStop = $train->stopTimes()->orderByDesc('stop_sequence')->first();
                if (!$lastStop) return false;
                
                $arrivalTime = Carbon::createFromFormat('H:i:s', $lastStop->arrival_time);
                $minutesSinceArrival = Carbon::now()->diffInMinutes($arrivalTime, false);
                return $this->compare($minutesSinceArrival, $this->value);
            
            case 'platform_change':
                // This would need specific logic for platform changes
                return false;
            
            case 'delay_duration':
                // This would need specific logic for delay calculations
                return false;
            
            case 'current_status':
                $currentStatus = $train->currentStatus;
                $statusValue = $currentStatus ? $currentStatus->status : 'on-time';
                
                // If the value is numeric (status ID), get the actual status text
                if (is_numeric($this->value)) {
                    $status = \App\Models\Status::find($this->value);
                    $compareValue = $status ? $status->status : $this->value;
                } else {
                    $compareValue = $this->value;
                }
                
                return $this->compare($statusValue, $compareValue);
            
            case 'time_of_day':
                $currentTime = Carbon::now()->format('H:i:s');
                return $this->compare($currentTime, $this->value);
            
            case 'train_number':
                $trainNumber = $train->trip_short_name ?? $train->trip_id;
                return $this->compare($trainNumber, $this->value);
            
            default:
                return false;
        }
    }

    private function compare($value1, $value2)
    {
        switch ($this->operator) {
            case '>':
                return $value1 > $value2;
            case '<':
                return $value1 < $value2;
            case '=':
                return $value1 == $value2;
            default:
                return false;
        }
    }
} 