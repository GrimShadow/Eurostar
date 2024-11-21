<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsStopTime extends Model
{
    protected $fillable = [
        'trip_id',
        'arrival_time',
        'departure_time',
        'stop_id',
        'stop_sequence',
        'drop_off_type',
        'pickup_type'
    ];

    protected $casts = [
        'stop_sequence' => 'integer',
        'drop_off_type' => 'integer',
        'pickup_type' => 'integer'
    ];
}