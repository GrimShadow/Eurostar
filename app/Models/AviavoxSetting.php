<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviavoxSetting extends Model
{
    protected $fillable = [
        'ip_address',
        'port',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}