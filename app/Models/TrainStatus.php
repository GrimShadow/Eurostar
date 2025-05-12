<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainStatus extends Model
{
    protected $fillable = [
        'trip_id',
        'status'
    ];

    public function trip()
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }
}