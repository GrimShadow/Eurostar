<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsHeartbeat extends Model
{
    protected $fillable = [
        'timestamp',
        'status',
        'status_reason',
        'last_update_sent_timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'last_update_sent_timestamp' => 'datetime'
    ];
}