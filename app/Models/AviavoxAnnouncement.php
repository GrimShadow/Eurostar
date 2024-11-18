<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviavoxAnnouncement extends Model
{
    protected $fillable = [
        'name',
        'item_id',
        'value',
    ];
}
