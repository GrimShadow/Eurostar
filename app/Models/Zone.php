<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['item_id', 'value'];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_zones')
            ->withTimestamps();
    }
}
