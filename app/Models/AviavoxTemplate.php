<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviavoxTemplate extends Model
{
    protected $fillable = ['name', 'xml_template', 'variables'];

    protected $casts = [
        'variables' => 'array'
    ];
} 