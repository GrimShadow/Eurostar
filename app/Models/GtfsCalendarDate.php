<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GtfsCalendarDate extends Model
{
    protected $fillable = [
        'service_id',
        'date',
        'exception_type'
    ];

    protected $casts = [
        'date' => 'date',
        'exception_type' => 'integer'
    ];
}