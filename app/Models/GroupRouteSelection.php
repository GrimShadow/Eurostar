<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRouteSelection extends Model
{
    protected $fillable = [
        'group_id',
        'route_id',
        'is_active'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
} 