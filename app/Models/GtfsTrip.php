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
}