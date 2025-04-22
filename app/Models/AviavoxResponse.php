<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviavoxResponse extends Model
{
    protected $fillable = [
        'announcement_id',
        'status',
        'message_name',
        'raw_response'
    ];

    protected $casts = [
        'raw_response' => 'string'
    ];
} 