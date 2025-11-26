<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'color_name',
        'color_rgb',
    ];

    protected $casts = [
        'color_rgb' => 'string',
    ];
}
