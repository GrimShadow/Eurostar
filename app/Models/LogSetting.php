<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSetting extends Model
{
    protected $fillable = [
        'gtfs_error_logs',
        'gtfs_debug_logs',
        'gtfs_information_logs',
        'aviavox_error_logs',
        'aviavox_debug_logs',
        'aviavox_information_logs',
        'automatic_rules_error_logs',
        'automatic_rules_debug_logs',
        'automatic_rules_information_logs',
        'announcement_error_logs',
        'announcement_debug_logs',
        'announcement_information_logs',
    ];

    protected $casts = [
        'gtfs_error_logs' => 'boolean',
        'gtfs_debug_logs' => 'boolean',
        'gtfs_information_logs' => 'boolean',
        'aviavox_error_logs' => 'boolean',
        'aviavox_debug_logs' => 'boolean',
        'aviavox_information_logs' => 'boolean',
        'automatic_rules_error_logs' => 'boolean',
        'automatic_rules_debug_logs' => 'boolean',
        'automatic_rules_information_logs' => 'boolean',
        'announcement_error_logs' => 'boolean',
        'announcement_debug_logs' => 'boolean',
        'announcement_information_logs' => 'boolean',
    ];
}
