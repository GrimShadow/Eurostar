<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'train_rule_id',
        'condition_type',
        'operator',
        'value',
        'logical_operator',
        'order',
        'tolerance_minutes',
        'group_id',
        'group_operator',
        'nesting_level',
        'value_secondary',
        'threshold_type',
        'reference_field',
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
                if (! $stopTime) {
                    return false;
                }

                $now = Carbon::now();
                $today = $now->format('Y-m-d');

                // Use updated departure time if available, otherwise use scheduled time
                $departureTimeString = $stopTime->new_departure_time ?? $stopTime->departure_time;
                $departureTime = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.$departureTimeString);

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
                if (! $stopTime) {
                    return false;
                }

                $now = Carbon::now();
                $today = $now->format('Y-m-d');

                // Use updated departure time if available, otherwise use scheduled time
                $departureTimeString = $stopTime->new_departure_time ?? $stopTime->departure_time;
                $departureTime = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.$departureTimeString);

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
                if (! $stopTime) {
                    return false;
                }

                $now = Carbon::now();
                $today = $now->format('Y-m-d');

                // Create arrival time for today
                $arrivalTime = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.$stopTime->arrival_time);

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
                if (! $stopTime) {
                    return false;
                }

                $arrivalTime = Carbon::createFromFormat('H:i:s', $stopTime->arrival_time);
                $now = Carbon::now();

                // Calculate minutes after arrival (positive = arrived, negative = not yet arrived)
                $minutesAfterArrival = $now->diffInMinutes($arrivalTime, false);

                return $this->compare($minutesAfterArrival, $this->value);

            case 'minutes_until_check_in_starts':
                // Compute minutes until check-in starts for the first (or specific) stop
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
                }
                if (! $stopTime) {
                    return false;
                }

                $now = Carbon::now();
                $today = $now->format('Y-m-d');

                // Determine check-in offset minutes
                $globalCheckInOffset = \App\Models\Setting::where('key', 'global_check_in_offset')->value('value') ?? 90;
                $specificTrainTimes = \App\Models\Setting::where('key', 'specific_train_check_in_times')->value('value');
                $specificTrainTimes = $specificTrainTimes ? (is_array($specificTrainTimes) ? $specificTrainTimes : json_decode($specificTrainTimes, true)) : [];

                // Derive a train number similar to API logic
                $trainNumberRaw = $train->trip_short_name ?? $train->trip_id;
                $trainNumber = explode(' ', (string) $trainNumberRaw)[0];
                $checkInOffset = (int) ($specificTrainTimes[$trainNumber] ?? $globalCheckInOffset);

                // Always use scheduled departure_time for check-in calculations (never new_departure_time)
                // so check-in time only changes manually, not automatically with delays
                $departureTime = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.$stopTime->departure_time);
                $checkInStart = $departureTime->copy()->subMinutes($checkInOffset);

                // If the computed check-in start is in the past but departure is still in the future, it means check-in already started
                if ($checkInStart->isPast()) {
                    if ($departureTime->isFuture()) {
                        $minutesUntilCheckIn = 0;
                    } else {
                        // Both are in the past for today; assume next day schedule
                        $checkInStart = $checkInStart->addDay();
                        $minutesUntilCheckIn = (int) round($now->diffInMinutes($checkInStart, false));
                    }
                } else {
                    $minutesUntilCheckIn = (int) round($now->diffInMinutes($checkInStart, false));
                }

                if ($minutesUntilCheckIn < 0) {
                    $minutesUntilCheckIn = 0;
                }

                return $this->compare($minutesUntilCheckIn, $this->value);

            case 'time_since_arrival':
                // Get the last stop's arrival time (or specific stop if provided)
                if ($specificStopId) {
                    $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
                } else {
                    $stopTime = $train->stopTimes()->orderByDesc('stop_sequence')->first();
                }
                if (! $stopTime) {
                    return false;
                }

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
                    if (! $firstStopTime) {
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

            case 'check_in_status':
                // Get the check-in status for the train
                $trainCheckInStatus = \App\Models\TrainCheckInStatus::where('trip_id', $train->trip_id)->first();

                // If no check-in status is set, the condition cannot match (return false)
                if (! $trainCheckInStatus || ! $trainCheckInStatus->checkInStatus) {
                    // For != operator, null/no status means it doesn't match, so return true
                    // For = operator, null/no status means it doesn't match, so return false
                    if ($this->operator === '!=') {
                        // If checking for "not equal", and there's no status, it's not equal to the specified value
                        return true;
                    }

                    return false;
                }

                $checkInStatusValue = $trainCheckInStatus->checkInStatus->status;

                // If the value is numeric (check-in status ID), get the actual status text
                if (is_numeric($this->value)) {
                    $checkInStatus = \App\Models\CheckInStatus::find($this->value);
                    $compareValue = $checkInStatus ? $checkInStatus->status : $this->value;
                } else {
                    $compareValue = $this->value;
                }

                // Normalize both values for comparison (case-insensitive, handle dash/space differences)
                $normalizedCheckInStatusValue = strtolower(str_replace([' ', '-'], '', $checkInStatusValue));
                $normalizedCompareValue = strtolower(str_replace([' ', '-'], '', $compareValue));

                return $this->compare($normalizedCheckInStatusValue, $normalizedCompareValue);

            case 'time_of_day':
                $currentTime = Carbon::now()->format('H:i:s');

                return $this->compare($currentTime, $this->value);

            case 'train_number':
                $trainNumber = $train->trip_short_name ?? $train->trip_id;
                // Extract just the train number (first part before space)
                $extractedTrainNumber = explode(' ', $trainNumber)[0];

                return $this->compare($extractedTrainNumber, $this->value);

                // Realtime Data Conditions
            case 'delay_minutes':
                return $this->evaluateDelayMinutes($train, $specificStopId);

            case 'delay_percentage':
                return $this->evaluateDelayPercentage($train, $specificStopId);

            case 'platform_changed':
                return $this->evaluatePlatformChanged($train, $specificStopId);

            case 'specific_platform':
                return $this->evaluateSpecificPlatform($train, $specificStopId);

            case 'is_cancelled':
                return $this->evaluateIsCancelled($train, $specificStopId);

            case 'has_realtime_update':
                return $this->evaluateHasRealtimeUpdate($train, $specificStopId);

                // Route/Direction Conditions
            case 'route_id':
                return $this->compare($train->route_id, $this->value);

            case 'direction_id':
                return $this->compare($train->direction_id, $this->value);

            case 'destination_station':
                return $this->evaluateDestinationStation($train, $specificStopId);

                // Time Window Conditions
            case 'time_range':
                return $this->evaluateTimeRange($train, $specificStopId);

            case 'day_of_week':
                return $this->evaluateDayOfWeek($train, $specificStopId);

            case 'is_peak_time':
                return $this->evaluateIsPeakTime($train, $specificStopId);

                // Service Conditions
            case 'wheelchair_accessible':
                return $this->compare($train->wheelchair_accessible, $this->value);

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
                // For time-based conditions, use tolerance window to handle timing issues
                if (in_array($this->condition_type, ['time_until_departure', 'time_after_departure', 'time_until_arrival', 'time_after_arrival'])) {
                    $tolerance = $this->tolerance_minutes ?? 1;

                    return abs($value1 - $value2) <= $tolerance;
                }

                return $value1 == $value2;
            case '!=':
                return $value1 != $value2;
            default:
                return false;
        }
    }

    // New condition evaluation methods
    private function evaluateDelayMinutes($train, $specificStopId = null)
    {
        if ($specificStopId) {
            $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
        } else {
            $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
        }

        if (! $stopTime || ! $stopTime->new_departure_time) {
            return false;
        }

        $scheduledTime = Carbon::createFromFormat('H:i:s', $stopTime->departure_time);
        $actualTime = Carbon::createFromFormat('H:i:s', $stopTime->new_departure_time);
        $delayMinutes = $scheduledTime->diffInMinutes($actualTime, false);

        return $this->compare($delayMinutes, $this->value);
    }

    private function evaluateDelayPercentage($train, $specificStopId = null)
    {
        // Calculate total journey time
        $firstStop = $train->stopTimes()->orderBy('stop_sequence')->first();
        $lastStop = $train->stopTimes()->orderByDesc('stop_sequence')->first();

        if (! $firstStop || ! $lastStop) {
            return false;
        }

        $journeyTime = Carbon::createFromFormat('H:i:s', $firstStop->departure_time)
            ->diffInMinutes(Carbon::createFromFormat('H:i:s', $lastStop->arrival_time));

        $delayMinutes = $this->getDelayMinutes($train, $specificStopId);
        if ($delayMinutes === false) {
            return false;
        }

        $delayPercentage = ($delayMinutes / $journeyTime) * 100;

        return $this->compare($delayPercentage, $this->value);
    }

    private function evaluatePlatformChanged($train, $specificStopId = null)
    {
        if ($specificStopId) {
            $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
        } else {
            $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
        }

        if (! $stopTime) {
            return false;
        }

        $stopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
            ->where('stop_id', $stopTime->stop_id)
            ->first();

        if (! $stopStatus) {
            return false;
        }

        $scheduledPlatform = $stopTime->stop->platform_code ?? '';
        $actualPlatform = $stopStatus->departure_platform ?? '';

        return $scheduledPlatform !== $actualPlatform;
    }

    private function evaluateSpecificPlatform($train, $specificStopId = null)
    {
        if ($specificStopId) {
            $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
        } else {
            $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
        }

        if (! $stopTime) {
            return false;
        }

        $stopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
            ->where('stop_id', $stopTime->stop_id)
            ->first();

        if (! $stopStatus) {
            return false;
        }

        return $this->compare($stopStatus->departure_platform, $this->value);
    }

    private function evaluateIsCancelled($train, $specificStopId = null)
    {
        $query = \App\Models\StopStatus::where('trip_id', $train->trip_id);

        if ($specificStopId) {
            $query->where('stop_id', $specificStopId);
        }

        return $query->where('status', 'cancelled')->exists();
    }

    private function evaluateHasRealtimeUpdate($train, $specificStopId = null)
    {
        $query = \App\Models\StopStatus::where('trip_id', $train->trip_id)
            ->where('is_realtime_update', true);

        if ($specificStopId) {
            $query->where('stop_id', $specificStopId);
        }

        return $query->exists();
    }

    private function evaluateDestinationStation($train, $specificStopId = null)
    {
        $lastStop = $train->stopTimes()->orderByDesc('stop_sequence')->first();
        if (! $lastStop) {
            return false;
        }

        $destinationId = $lastStop->stop_id;
        $destinationName = $lastStop->stop->stop_name ?? '';

        return $this->compare($destinationId, $this->value) ||
               $this->compare($destinationName, $this->value);
    }

    private function evaluateTimeRange($train, $specificStopId = null)
    {
        $currentTime = Carbon::now()->format('H:i:s');
        $startTime = $this->value;
        $endTime = $this->value_secondary;

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    private function evaluateDayOfWeek($train, $specificStopId = null)
    {
        $currentDay = Carbon::now()->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        $allowedDays = explode(',', $this->value);

        return in_array($currentDay, $allowedDays);
    }

    private function evaluateIsPeakTime($train, $specificStopId = null)
    {
        $currentTime = Carbon::now();
        $hour = $currentTime->hour;

        // Define peak hours: 7-9am and 4-7pm
        $morningPeak = $hour >= 7 && $hour < 9;
        $eveningPeak = $hour >= 16 && $hour < 19;

        return $morningPeak || $eveningPeak;
    }

    private function getDelayMinutes($train, $specificStopId = null)
    {
        if ($specificStopId) {
            $stopTime = $train->stopTimes()->where('stop_id', $specificStopId)->first();
        } else {
            $stopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
        }

        if (! $stopTime || ! $stopTime->new_departure_time) {
            return false;
        }

        $scheduledTime = Carbon::createFromFormat('H:i:s', $stopTime->departure_time);
        $actualTime = Carbon::createFromFormat('H:i:s', $stopTime->new_departure_time);

        return $scheduledTime->diffInMinutes($actualTime, false);
    }
}
