<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsSetting extends Model
{
    protected $fillable = [
        'url',
        'realtime_url',
        'realtime_update_interval',
        'last_realtime_update',
        'realtime_status',
        'realtime_source',
        'secondary_realtime_url',
        'secondary_realtime_update_interval',
        'is_active',
        'last_download',
        'next_download',
        'download_progress',
        'download_started_at',
        'download_status',
        'is_downloading',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_download' => 'datetime',
        'next_download' => 'datetime',
        'download_started_at' => 'datetime',
        'last_realtime_update' => 'datetime',
        'is_downloading' => 'boolean',
        'download_progress' => 'integer',
        'realtime_update_interval' => 'integer',
        'secondary_realtime_update_interval' => 'integer',
        'realtime_source' => 'string',
    ];
}
