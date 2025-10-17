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
        'stop_id',
        'stop_sequence',
        'drop_off_type',
        'pickup_type',
    ];

    protected $casts = [
        'stop_sequence' => 'integer',
        'drop_off_type' => 'integer',
        'pickup_type' => 'integer',
    ];

    public function stop()
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }

    public function trip()
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }
}
