<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsUpdate extends Model
{
    protected $fillable = [
        'gtfs_realtime_version',
        'incrementality',
        'timestamp',
        'entity_data'
    ];

    protected $casts = [
        'entity_data' => 'array',
        'timestamp' => 'datetime'
    ];
}