<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelectedRoute extends Model
{
    protected $fillable = [
        'route_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
