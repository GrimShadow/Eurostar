<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtfsStopTime extends Model
{
    use HasFactory;

    protected $table = 'gtfs_stop_times';

    protected $primaryKey = ['trip_id', 'stop_sequence'];

    public $incrementing = false;

    protected $fillable = [
        'trip_id',
        'arrival_time',
        'departure_time',
        'new_departure_time',
        'is_manual_change',
        'manually_changed_by',
        'manually_changed_at',
        'stop_id',
        'stop_sequence',
        'drop_off_type',
        'pickup_type',
    ];

    protected $casts = [
        'stop_sequence' => 'integer',
        'drop_off_type' => 'integer',
        'pickup_type' => 'integer',
        'is_manual_change' => 'boolean',
        'manually_changed_at' => 'datetime',
    ];

    public function stop()
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }

    public function trip()
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }

    /**
     * Get the user who manually changed this stop time
     */
    public function manuallyChangedBy()
    {
        return $this->belongsTo(User::class, 'manually_changed_by');
    }
}
