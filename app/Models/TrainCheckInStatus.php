<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainCheckInStatus extends Model
{
    protected $fillable = [
        'trip_id',
        'check_in_status_id',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }

    public function checkInStatus(): BelongsTo
    {
        return $this->belongsTo(CheckInStatus::class);
    }
}
