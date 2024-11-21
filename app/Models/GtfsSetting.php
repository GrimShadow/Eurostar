<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsSetting extends Model
{
    protected $fillable = [
        'url',
        'is_active',
        'last_download',
        'next_download'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_download' => 'datetime',
        'next_download' => 'datetime',
    ];
}