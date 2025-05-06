<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRouteStation extends Model
{
    protected $fillable = [
        'group_id',
        'route_id',
        'stop_id',
        'is_active'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function stop()
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }
} 