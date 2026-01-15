<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealtimeConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'stop_id',
        'field_type',
        'manual_value',
        'realtime_value',
        'manual_user_id',
        'resolved_by_user_id',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the user who made the manual change
     */
    public function manualUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_user_id');
    }

    /**
     * Get the user who resolved the conflict
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * Get the trip
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(GtfsTrip::class, 'trip_id', 'trip_id');
    }

    /**
     * Get the stop
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(GtfsStop::class, 'stop_id', 'stop_id');
    }

    /**
     * Check if the conflict is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolution !== null && $this->resolved_at !== null;
    }

    /**
     * Resolve the conflict
     */
    public function resolve(string $resolution, ?int $userId = null): bool
    {
        if (! in_array($resolution, ['use_realtime', 'keep_manual'])) {
            return false;
        }

        $this->resolution = $resolution;
        $this->resolved_by_user_id = $userId ?? auth()->id();
        $this->resolved_at = now();

        return $this->save();
    }

    /**
     * Scope to get unresolved conflicts
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolution')->whereNull('resolved_at');
    }
}
