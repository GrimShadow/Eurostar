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
                $departureTime = Carbon::createFromFormat('H:i:s', $train->departure_time);
                $minutesUntilDeparture = $departureTime->diffInMinutes(Carbon::now());
                return $this->compare($minutesUntilDeparture, $this->value);
            
            case 'time_since_arrival':
                $arrivalTime = Carbon::createFromFormat('H:i:s', $train->arrival_time);
                $minutesSinceArrival = Carbon::now()->diffInMinutes($arrivalTime);
                return $this->compare($minutesSinceArrival, $this->value);
            
            case 'platform_change':
                return $this->compare($train->platform, $this->value);
            
            case 'delay_duration':
                return $this->compare($train->delay_minutes, $this->value);
            
            case 'current_status':
                return $this->compare($train->status, $this->value);
            
            case 'time_of_day':
                $currentTime = Carbon::now()->format('H:i:s');
                return $this->compare($currentTime, $this->value);
            
            case 'train_number':
                return $this->compare($train->number, $this->value);
            
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