<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainRule extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'value' => 'integer'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'action_value');
    }
}
