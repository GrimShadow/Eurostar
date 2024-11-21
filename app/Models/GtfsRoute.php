<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsRoute extends Model
{
    protected $fillable = [
        'route_id',
        'agency_id',
        'route_short_name',
        'route_long_name',
        'route_type',
        'route_color',
        'route_text_color'
    ];

    protected $casts = [
        'route_type' => 'integer'
    ];
}