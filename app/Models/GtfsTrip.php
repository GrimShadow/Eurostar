<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsTrip extends Model
{
    protected $fillable = [
        'route_id',
        'service_id',
        'trip_id',
        'trip_headsign',
        'trip_short_name',
        'direction_id',
        'shape_id',
        'wheelchair_accessible'
    ];

    protected $casts = [
        'direction_id' => 'integer',
        'wheelchair_accessible' => 'boolean'
    ];

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