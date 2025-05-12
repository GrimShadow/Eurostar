<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StopStatus extends Model
{
    protected $fillable = [
        'trip_id',
        'stop_id',
        'status',
        'status_color',
        'status_color_hex',
        'scheduled_arrival_time',
        'scheduled_departure_time',
        'actual_arrival_time',
        'actual_departure_time',
        'platform_code',
        'departure_platform',
        'arrival_platform'
    ];

    protected $casts = [
        'scheduled_arrival_time' => 'datetime',
        'scheduled_departure_time' => 'datetime',
        'actual_arrival_time' => 'datetime',
        'actual_departure_time' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }

    public function stop()
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stopStatus) {
            if (!$stopStatus->status_color) {
                $stopStatus->status_color = '156,163,175';
            }
            if (!$stopStatus->status_color_hex) {
                $stopStatus->status_color_hex = '#9CA3AF';
            }
            if (!$stopStatus->departure_platform) {
                $stopStatus->departure_platform = 'TBD';
            }
            if (!$stopStatus->arrival_platform) {
                $stopStatus->arrival_platform = 'TBD';
            }
        });
    }
} 