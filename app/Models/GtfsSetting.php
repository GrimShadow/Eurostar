<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsSetting extends Model
{
    protected $fillable = [
        'url',
        'is_active',
        'last_download',
        'next_download',
        'download_progress',
        'download_started_at',
        'download_status',
        'is_downloading'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_download' => 'datetime',
        'next_download' => 'datetime',
        'download_started_at' => 'datetime',
        'is_downloading' => 'boolean',
        'download_progress' => 'integer'
    ];
}