<?php

namespace App\Jobs;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use App\Models\StopStatus;
use App\Models\Group;
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
                //Log::info("Rule {$this->ruleId} not found or inactive, skipping");
                return;
            }


            // Get trains that are visible in train grids (filtered by group configurations)
            $trains = $this->getRelevantTrains();
            

            foreach ($trains as $train) {
                $this->processRuleForTrain($rule, $train);
            }

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
        // Get all active groups and their train grid configurations
        $groups = \App\Models\Group::where('active', true)->get();
        
        if ($groups->isEmpty()) {
            return collect();
        }

        $allTrainIds = collect();
        
        foreach ($groups as $group) {
            // Get selected routes for this group (same logic as TrainGrid)
            $apiRoutes = DB::table('selected_routes')
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            $groupRoutes = $group->selectedRoutes()
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            // Combine both sets of routes
            $selectedRoutes = array_unique(array_merge($apiRoutes, $groupRoutes));
            
            if (empty($selectedRoutes)) {
                continue;
            }

            // Get selected stations for this group
            $selectedStations = $group->routeStations()
                ->where('is_active', true)
                ->get()
                ->groupBy('route_id')
                ->map(function ($stations) {
                    return $stations->pluck('stop_id')->toArray();
                })
                ->toArray();

            // Set time range (same as TrainGrid)
            $currentTime = now()->subMinutes(30)->format('H:i:s');
            $endTime = '23:59:59';

            // Collect all selected station IDs for this group
            $allSelectedStations = [];
            foreach ($selectedRoutes as $routeId) {
                if (!empty($selectedStations[$routeId])) {
                    $allSelectedStations = array_merge($allSelectedStations, $selectedStations[$routeId]);
                }
            }
            $allSelectedStations = array_unique($allSelectedStations);

            // Skip this group if no stations are selected
            if (empty($allSelectedStations)) {
                continue;
            }

            // Get unique trips for today that match this group's configuration
            $groupTrainIds = DB::table('gtfs_trips')
                ->select('gtfs_trips.trip_id')
                ->whereIn('gtfs_trips.route_id', $selectedRoutes)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_calendar_dates')
                        ->whereColumn('gtfs_calendar_dates.service_id', 'gtfs_trips.service_id')
                        ->where('gtfs_calendar_dates.date', now()->format('Y-m-d'))
                        ->where('gtfs_calendar_dates.exception_type', 1);
                })
                ->whereExists(function ($query) use ($currentTime, $endTime) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_stop_times')
                        ->whereColumn('gtfs_stop_times.trip_id', 'gtfs_trips.trip_id')
                        ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
                        ->where('gtfs_stop_times.departure_time', '<=', $endTime);
                })
                ->whereExists(function ($query) use ($allSelectedStations) {
                    $query->select(DB::raw(1))
                        ->from('gtfs_stop_times')
                        ->whereColumn('gtfs_stop_times.trip_id', 'gtfs_trips.trip_id')
                        ->whereIn('gtfs_stop_times.stop_id', $allSelectedStations);
                })
                ->pluck('trip_id');

            $allTrainIds = $allTrainIds->merge($groupTrainIds);
        }

        // Remove duplicates and get the actual train models
        $uniqueTrainIds = $allTrainIds->unique();
        
        if ($uniqueTrainIds->isEmpty()) {
            //Log::info("No trains found matching any group's train grid configuration");
            return collect();
        }

        //Log::info("Found {$uniqueTrainIds->count()} unique trains across all groups' train grids");

        // Get trains with minimal relationships to reduce memory usage
        return \App\Models\GtfsTrip::with(['currentStatus', 'stopTimes' => function($query) {
                // Only get first and last stops to minimize data
                $query->orderBy('stop_sequence');
            }])
            ->whereIn('trip_id', $uniqueTrainIds->toArray())
            ->limit(500) // Keep the safety limit
            ->get();
    }

    private function processRuleForTrain($rule, $train)
    {
        try {
            // Get all configured stops for this train
            $configuredStops = $this->getConfiguredStopsForTrain($train);
            
            if ($configuredStops->isEmpty()) {
                //Log::info("Train {$train->trip_id} has no configured stops, skipping");
                return;
            }

            //Log::info("Checking rule {$rule->id} for train {$train->trip_id} at " . $configuredStops->count() . " configured stops");
            
            $stopsToUpdate = [];
            
            // Check each configured stop individually
            foreach ($configuredStops as $stopTime) {
                $stopStatus = StopStatus::where('trip_id', $train->trip_id)
                    ->where('stop_id', $stopTime->stop_id)
                    ->first();
                $currentStatus = $stopStatus ? $stopStatus->status : 'On Time';
                
                // Check if rule condition is met for this specific stop
                $conditionMet = $this->shouldTriggerForStop($rule, $train, $stopTime->stop_id);
                
                //Log::info("Stop {$stopTime->stop_id}: status='{$currentStatus}', condition " . ($conditionMet ? 'MET' : 'NOT MET'));
                
                if ($conditionMet) {
                    $stopsToUpdate[] = $stopTime->stop_id;
                }
            }
            
            if (!empty($stopsToUpdate)) {
                //Log::info("Rule {$rule->id} condition met for train {$train->trip_id} at stops: " . implode(', ', $stopsToUpdate) . ", applying action: {$rule->action}");
                $this->applyActionToStops($rule, $train, $stopsToUpdate);
            } else {
                //Log::info("Rule {$rule->id} condition NOT met for train {$train->trip_id} at any configured stops");
            }
        } catch (\Exception $e) {
            Log::error("Error processing rule {$rule->id} for train {$train->trip_id}: " . $e->getMessage());
            // Don't re-throw here to continue processing other trains
        }
    }

    private function shouldTriggerForStop($rule, $train, $stopId)
    {
        $conditions = $rule->conditions;
        
        if ($conditions->isEmpty()) {
            return false;
        }

        $result = $conditions->first()->evaluate($train, $stopId);
        
        foreach ($conditions->skip(1) as $condition) {
            if ($condition->logical_operator === 'and') {
                $result = $result && $condition->evaluate($train, $stopId);
            } else {
                $result = $result || $condition->evaluate($train, $stopId);
            }
        }

        return $result;
    }

    private function applyAction($rule, $train)
    {
        if ($rule->action === 'set_status') {
            $this->setTrainStatus($rule, $train);
        } elseif ($rule->action === 'make_announcement') {
            $this->makeAnnouncement($rule, $train);
        }
    }

    private function applyActionToStops($rule, $train, $stopIds)
    {
        if ($rule->action === 'set_status') {
            $this->setTrainStatusAtStops($rule, $train, $stopIds);
        } elseif ($rule->action === 'make_announcement') {
            $this->makeAnnouncement($rule, $train);
        }
    }

    private function setTrainStatusAtStops($rule, $train, $stopIds)
    {
        // Get the status text from the statuses table
        $status = Status::find($rule->action_value);
        if (!$status) {
            Log::error("Status with ID {$rule->action_value} not found for rule {$rule->id}");
            return;
        }

        $updatedStops = [];
        
        foreach ($stopIds as $stopId) {
            // Check if status has already been set to prevent unnecessary updates
            $currentStopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                ->where('stop_id', $stopId)
                ->first();
                
            if ($currentStopStatus && $currentStopStatus->status === $status->status) {
                continue; // Skip if already set
            }

            // Update or create the status in stop_statuses table
            \App\Models\StopStatus::updateOrCreate(
                [
                    'trip_id' => $train->trip_id,
                    'stop_id' => $stopId
                ],
                ['status' => $status->status]
            );
            
            $updatedStops[] = $stopId;
        }

        if (!empty($updatedStops)) {
            // Update or create the status in train_statuses table (for backward compatibility)
            TrainStatus::updateOrCreate(
                ['trip_id' => $train->trip_id],
                ['status' => $status->status]
            );

            //Log::info("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

            // Broadcast the status change event
            event(new TrainStatusUpdated($train->trip_id, $status->status));
        } else {
            //Log::info("Train {$train->trip_id} already has status {$status->status} at specified stops, skipping");
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

        // Get all stops for this train that are configured in any group's train grid
        $allConfiguredStops = $this->getConfiguredStopsForTrain($train);
        
        if ($allConfiguredStops->isEmpty()) {
            Log::warning("No configured stops found for train {$train->trip_id}");
            return;
        }

        $updatedStops = [];
        
        foreach ($allConfiguredStops as $stopTime) {
            // Check if status has already been set to prevent unnecessary updates
            $currentStopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                ->where('stop_id', $stopTime->stop_id)
                ->first();
                
            if ($currentStopStatus && $currentStopStatus->status === $status->status) {
                continue; // Skip if already set
            }

            // Update or create the status in stop_statuses table (what the train grids use)
            \App\Models\StopStatus::updateOrCreate(
                [
                    'trip_id' => $train->trip_id,
                    'stop_id' => $stopTime->stop_id
                ],
                ['status' => $status->status]
            );
            
            $updatedStops[] = $stopTime->stop_id;
        }

        if (!empty($updatedStops)) {
            // Update or create the status in train_statuses table (for backward compatibility)
            TrainStatus::updateOrCreate(
                ['trip_id' => $train->trip_id],
                ['status' => $status->status]
            );

            //Log::info("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

            // Broadcast the status change event
            event(new TrainStatusUpdated($train->trip_id, $status->status));
        } else {
            //Log::info("Train {$train->trip_id} already has status {$status->status} at all configured stops, skipping");
        }
    }

    private function getConfiguredStopsForTrain($train)
    {
        // Get all stops for this train that are configured in any active group
        $allConfiguredStopIds = collect();
        
        $groups = Group::where('active', true)->get();
        foreach ($groups as $group) {
            $selectedStations = $group->routeStations()
                ->where('is_active', true)
                ->where('route_id', $train->route_id)
                ->pluck('stop_id');
            
            $allConfiguredStopIds = $allConfiguredStopIds->merge($selectedStations);
        }
        
        $uniqueStopIds = $allConfiguredStopIds->unique();
        
        // Get the stop times for these configured stops
        return $train->stopTimes()
            ->whereIn('stop_id', $uniqueStopIds->toArray())
            ->orderBy('stop_sequence')
            ->get();
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
            //Log::info("Recent announcement for template {$template->name} already exists, skipping");
            return;
        }

        //Log::info("Making announcement for train {$train->trip_id} using template {$template->name}", [
        //    'zone' => $announcementData['zone'] ?? 'Unknown',
        //    'variables' => $announcementData['variables'] ?? []
        //]);

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