<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDate extends Model
{
    protected $table = 'calendar_dates';

    protected $fillable = [
        'service_id',
        'date',
        'exception_type'
    ];

    public function trips()
    {
        return $this->hasMany(GtfsTrip::class, 'service_id', 'service_id');
    }
} 