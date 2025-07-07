<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainRuleExecution extends Model
{
    protected $fillable = [
        'rule_id',
        'trip_id',
        'stop_id',
        'executed_at',
        'action_taken',
        'action_details'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'action_details' => 'array'
    ];

    public function rule()
    {
        return $this->belongsTo(TrainRule::class);
    }

    /**
     * Check if a rule has already been executed for a specific train/stop combination
     */
    public static function hasBeenExecuted($ruleId, $tripId, $stopId)
    {
        return self::where('rule_id', $ruleId)
            ->where('trip_id', $tripId)
            ->where('stop_id', $stopId)
            ->exists();
    }

    /**
     * Record a rule execution (using updateOrCreate to handle duplicates gracefully)
     */
    public static function recordExecution($ruleId, $tripId, $stopId, $actionTaken, $actionDetails = null)
    {
        try {
            return self::updateOrCreate(
                [
                    'rule_id' => $ruleId,
                    'trip_id' => $tripId,
                    'stop_id' => $stopId
                ],
                [
                    'executed_at' => Carbon::now(),
                    'action_taken' => $actionTaken,
                    'action_details' => $actionDetails
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle foreign key constraint violations gracefully
            if ($e->getCode() === '23000') {
                \Illuminate\Support\Facades\Log::warning("Failed to record execution for rule {$ruleId} - rule may have been deleted");
                return null;
            }
            throw $e; // Re-throw other database errors
        }
    }

    /**
     * Clean up old executions (older than 24 hours)
     * This prevents the table from growing indefinitely
     */
    public static function cleanupOldExecutions()
    {
        return self::where('executed_at', '<', Carbon::now()->subHours(24))->delete();
    }
} 