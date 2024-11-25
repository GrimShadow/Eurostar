<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainStatus extends Model
{
    protected $fillable = ['trip_id', 'status'];
}