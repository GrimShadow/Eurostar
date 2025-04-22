<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Route;

class GroupTrainTableSelection extends Model
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

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
} 