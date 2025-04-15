<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsStop extends Model
{
    protected $fillable = [
        'stop_id',
        'stop_code',
        'stop_name',
        'stop_lon',
        'stop_lat',
        'stop_timezone',
        'location_type',
        'platform_code'
    ];

    protected $casts = [
        'stop_lon' => 'decimal:6',
        'stop_lat' => 'decimal:6',
        'location_type' => 'integer'
    ];
}