<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingAnnouncement extends Model
{
    protected $fillable = [
        'xml_content',
        'status',
        'response',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime'
    ];
}
