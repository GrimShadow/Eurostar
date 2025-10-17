<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtfsTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'service_id',
        'trip_id',
        'trip_headsign',
        'trip_short_name',
        'direction_id',
        'shape_id',
        'wheelchair_accessible',
    ];

    protected $casts = [
        'direction_id' => 'integer',
        'wheelchair_accessible' => 'boolean',
    ];

    /**
     * Parse the train number from trip_id (e.g., "9002-0809" -> "9002")
     */
    public function getTrainNumberAttribute()
    {
        if (strpos($this->trip_id, '-') !== false) {
            return explode('-', $this->trip_id)[0];
        }

        return $this->trip_short_name;
    }

    /**
     * Parse the date from trip_id (e.g., "9002-0809" -> "08-09")
     */
    public function getTrainDateAttribute()
    {
        if (strpos($this->trip_id, '-') !== false) {
            $parts = explode('-', $this->trip_id);
            if (count($parts) >= 2) {
                return $parts[1];
            }
        }

        return null;
    }

    /**
     * Parse the date from service_id (e.g., "9002-0809" -> "08-09")
     */
    public function getServiceDateAttribute()
    {
        if (strpos($this->service_id, '-') !== false) {
            $parts = explode('-', $this->service_id);
            if (count($parts) >= 2) {
                return $parts[1];
            }
        }

        return null;
    }

    /**
     * Get a formatted date string from the trip_id
     */
    public function getFormattedDateAttribute()
    {
        $date = $this->getTrainDateAttribute();
        if ($date && strlen($date) === 4) {
            $month = substr($date, 0, 2);
            $day = substr($date, 2, 2);

            return "{$month}-{$day}";
        }

        return $date;
    }

    /**
     * Get a human-readable date string
     */
    public function getHumanReadableDateAttribute()
    {
        $date = $this->getFormattedDateAttribute();
        if ($date) {
            try {
                $currentYear = Carbon::now()->year;
                $dateObj = Carbon::createFromFormat('m-d', $date)->year($currentYear);

                return $dateObj->format('M j');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }

    public function calendarDate()
    {
        return $this->belongsTo(CalendarDate::class, 'service_id', 'service_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'current_status');
    }

    public function currentStatus()
    {
        return $this->hasOne(TrainStatus::class, 'trip_id', 'trip_id');
    }

    public function route()
    {
        return $this->belongsTo(GtfsRoute::class, 'route_id', 'route_id');
    }

    public function stopTimes()
    {
        return $this->hasMany(GtfsStopTime::class, 'trip_id', 'trip_id')
            ->orderBy('stop_sequence');
    }
}
