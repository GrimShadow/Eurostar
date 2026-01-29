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
        'arrival_platform',
        'is_realtime_update',
        'is_manual_change',
        'manually_changed_by',
        'manually_changed_at',
    ];

    protected $casts = [
        'is_manual_change' => 'boolean',
        'manually_changed_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }

    public function stop()
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }

    /**
     * Get the user who manually changed this stop status
     */
    public function manuallyChangedBy()
    {
        return $this->belongsTo(User::class, 'manually_changed_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stopStatus) {
            if (! $stopStatus->status_color) {
                $stopStatus->status_color = '156,163,175';
            }
            if (! $stopStatus->status_color_hex) {
                $stopStatus->status_color_hex = '#9CA3AF';
            }
            if (! $stopStatus->departure_platform) {
                $stopStatus->departure_platform = 'TBD';
            }
            if (! $stopStatus->arrival_platform) {
                $stopStatus->arrival_platform = 'TBD';
            }
        });
    }
}
