<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'color_name',
        'color_rgb',
    ];

    protected $casts = [
        'color_rgb' => 'string',
    ];

    /**
     * Get the status configured as default for new trains (no StopStatus yet).
     */
    public static function getDefaultForNewTrains(): ?Status
    {
        $id = Setting::where('key', 'default_train_status_id')->value('value');
        if ($id === null || $id === '') {
            return null;
        }
        $id = is_array($id) ? ($id[0] ?? null) : $id;

        return self::find($id);
    }

    /**
     * Get the default status string for new trains (display/comparison). Fallback: "On Time".
     */
    public static function getDefaultStatusString(): string
    {
        $status = self::getDefaultForNewTrains();

        return $status?->status ?? 'On Time';
    }

    /**
     * Get the default status key (slug) for new trains, e.g. "on-time", "planned".
     */
    public static function getDefaultStatusKey(): string
    {
        $string = self::getDefaultStatusString();

        return strtolower(str_replace(' ', '-', $string));
    }
}
