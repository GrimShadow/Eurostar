<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AutomatedAnnouncementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'interval_minutes',
        'days_of_week',
        'aviavox_template_id',
        'zone',
        'template_variables',
        'last_triggered_at',
        'is_active'
    ];

    protected $casts = [
        'template_variables' => 'array',
        'last_triggered_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the aviavox template associated with this rule
     */
    public function aviavoxTemplate()
    {
        return $this->belongsTo(AviavoxTemplate::class);
    }

    /**
     * Check if the rule should trigger now
     */
    public function shouldTrigger(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        
        // Check if today is an active day
        $todayNumber = $now->dayOfWeek === 0 ? 7 : $now->dayOfWeek; // Convert Sunday from 0 to 7
        $activeDays = explode(',', $this->days_of_week);
        if (!in_array($todayNumber, $activeDays)) {
            return false;
        }

        // Check if current time is within the active time range
        $currentTime = $now->format('H:i:s');
        if ($currentTime < $this->start_time || $currentTime > $this->end_time) {
            return false;
        }

        // Check if enough time has passed since last trigger
        if ($this->last_triggered_at) {
            $minutesSinceLastTrigger = $this->last_triggered_at->diffInMinutes($now);
            if ($minutesSinceLastTrigger < $this->interval_minutes) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark the rule as triggered
     */
    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => Carbon::now()]);
    }

    /**
     * Get human readable days of week
     */
    public function getDaysOfWeekTextAttribute(): string
    {
        $days = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'];
        $activeDays = explode(',', $this->days_of_week);
        
        if (count($activeDays) === 7) {
            return 'Every day';
        } elseif (array_diff($activeDays, ['1', '2', '3', '4', '5']) === []) {
            return 'Weekdays';
        } elseif (array_diff($activeDays, ['6', '7']) === []) {
            return 'Weekends';
        } else {
            return implode(', ', array_map(fn($day) => $days[$day], $activeDays));
        }
    }
}
