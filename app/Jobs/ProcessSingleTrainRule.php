<?php

namespace App\Jobs;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use App\Models\StopStatus;
use App\Events\TrainStatusUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessSingleTrainRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ruleId;
    
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job should run before timing out.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($ruleId)
    {
        $this->ruleId = $ruleId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Fetch the rule with conditions
            $rule = TrainRule::with('conditions')->find($this->ruleId);
            
            if (!$rule || !$rule->is_active) {
                Log::info("Rule {$this->ruleId} not found or inactive, skipping");
                return;
            }

            Log::info("Processing rule {$rule->id}");

            // Get all relevant trains for today
            $trains = $this->getRelevantTrains();
            
            Log::info("Found {$trains->count()} trains to check against rule {$rule->id}");

            foreach ($trains as $train) {
                $this->processRuleForTrain($rule, $train);
            }

            Log::info("Completed processing rule {$rule->id}");

        } catch (\Exception $e) {
            Log::error("Error processing train rule {$this->ruleId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to trigger job retry mechanism
        } finally {
            // Ensure database connection is properly closed
            DB::disconnect();
        }
    }

    private function getRelevantTrains()
    {
        // Only get trains for today with minimal relationships to reduce memory usage
        return GtfsTrip::with(['currentStatus', 'stopTimes' => function($query) {
                // Only get first and last stops to minimize data
                $query->orderBy('stop_sequence');
            }])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('gtfs_calendar_dates')
                    ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                    ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                    ->where('gtfs_calendar_dates.exception_type', 1);
            })
            ->limit(500) // Limit to prevent memory issues
            ->get();
    }

    private function processRuleForTrain($rule, $train)
    {
        try {
            // Debug: Log the train's current status from StopStatus (what rules now check)
            $firstStopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
            if ($firstStopTime) {
                $stopStatus = StopStatus::where('trip_id', $train->trip_id)
                    ->where('stop_id', $firstStopTime->stop_id)
                    ->first();
                $statusValue = $stopStatus ? $stopStatus->status : 'On Time';
                Log::info("Train {$train->trip_id} status: '{$statusValue}' (explicit: " . ($stopStatus ? 'yes' : 'no') . ")");
            } else {
                Log::info("Train {$train->trip_id} status: 'On Time' (no stop times found)");
            }
            
            $conditionMet = $rule->shouldTrigger($train);
            
            if ($conditionMet) {
                Log::info("Rule {$rule->id} condition met for train {$train->trip_id}, applying action: {$rule->action}");
                $this->applyAction($rule, $train);
            } else {
                Log::info("Rule {$rule->id} condition NOT met for train {$train->trip_id}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing rule {$rule->id} for train {$train->trip_id}: " . $e->getMessage());
            // Don't re-throw here to continue processing other trains
        }
    }

    private function applyAction($rule, $train)
    {
        if ($rule->action === 'set_status') {
            $this->setTrainStatus($rule, $train);
        } elseif ($rule->action === 'make_announcement') {
            $this->makeAnnouncement($rule, $train);
        }
    }

    private function setTrainStatus($rule, $train)
    {
        // Get the status text from the statuses table
        $status = Status::find($rule->action_value);
        if (!$status) {
            Log::error("Status with ID {$rule->action_value} not found for rule {$rule->id}");
            return;
        }

        // Get the first stop (departure station) to check/update its status
        $firstStopTime = $train->stopTimes()->orderBy('stop_sequence')->first();
        if (!$firstStopTime) {
            Log::warning("No stop times found for train {$train->trip_id}");
            return;
        }

        // Check if status has already been set to prevent unnecessary updates
        $currentStopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
            ->where('stop_id', $firstStopTime->stop_id)
            ->first();
            
        if ($currentStopStatus && $currentStopStatus->status === $status->status) {
            Log::info("Train {$train->trip_id} already has status {$status->status}, skipping");
            return;
        }

        // Update or create the status in train_statuses table (for backward compatibility)
        $trainStatus = TrainStatus::updateOrCreate(
            ['trip_id' => $train->trip_id],
            ['status' => $status->status]
        );

        // Update or create the status in stop_statuses table (what the train grid uses)
        $stopStatus = \App\Models\StopStatus::updateOrCreate(
            [
                'trip_id' => $train->trip_id,
                'stop_id' => $firstStopTime->stop_id
            ],
            ['status' => $status->status]
        );

        Log::info("Set status for train {$train->trip_id} to {$status->status}");

        // Broadcast the status change event
        event(new TrainStatusUpdated($train->trip_id, $status->status));
    }

    private function makeAnnouncement($rule, $train)
    {
        $announcementData = json_decode($rule->action_value, true);
        
        if (!$announcementData || !isset($announcementData['template_id'])) {
            Log::error("Invalid announcement data for rule {$rule->id}");
            return;
        }

        $template = AviavoxTemplate::find($announcementData['template_id']);
        if (!$template) {
            Log::error("Template {$announcementData['template_id']} not found for rule {$rule->id}");
            return;
        }

        // Check if announcement was already made recently to prevent spam
        $recentAnnouncement = DB::table('announcements')
            ->where('message', $template->name)
            ->where('created_at', '>', now()->subMinutes(5))
            ->exists();

        if ($recentAnnouncement) {
            Log::info("Recent announcement for template {$template->name} already exists, skipping");
            return;
        }

        Log::info("Making announcement for train {$train->trip_id} using template {$template->name}", [
            'zone' => $announcementData['zone'] ?? 'Unknown',
            'variables' => $announcementData['variables'] ?? []
        ]);

        // Here you would integrate with your actual announcement system
        // For now, just log the announcement
        // You can implement the actual Aviavox API call here similar to CreateAnnouncement
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Train rule job failed for rule {$this->ruleId}: " . $exception->getMessage(), [
            'trace' => $exception->getTraceAsString()
        ]);
    }
} 