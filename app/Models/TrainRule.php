<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'condition_type',
        'operator',
        'value',
        'action',
        'action_value',
        'is_active',
        'announcement_text'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'string'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'action_value');
    }

    public function conditionStatus()
    {
        return $this->belongsTo(Status::class, 'value');
    }

    public function getValueAttribute($value)
    {
        if ($this->condition_type === 'current_status') {
            return $value;
        }
        return (int)$value;
    }
}
