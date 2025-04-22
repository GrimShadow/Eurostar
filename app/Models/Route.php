<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'route_id',
        'route_short_name',
        'route_long_name',
        'route_type',
        'route_color',
        'route_text_color',
        'route_desc'
    ];

    public function groupSelections()
    {
        return $this->hasMany(GroupRouteSelection::class, 'route_id', 'route_id');
    }

    public function groupTrainTableSelections()
    {
        return $this->hasMany(GroupTrainTableSelection::class, 'route_id', 'route_id');
    }
} 