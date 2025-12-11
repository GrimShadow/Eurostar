<?php

namespace App\Jobs;

use App\Events\TrainStatusUpdated;
use App\Models\AviavoxTemplate;
use App\Models\CheckInStatus;
use App\Models\Group;
use App\Models\Status;
use App\Models\StopStatus;
use App\Models\TrainCheckInStatus;
use App\Models\TrainRule;
use App\Models\TrainRuleExecution;
use App\Models\TrainStatus;
use App\Services\LogHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            if (! $rule) {
                LogHelper::rulesInfo("Rule {$this->ruleId} not found (possibly deleted), skipping job execution");

                return;
            }

            if (! $rule->is_active) {
                //LogHelper::rulesInfo("Rule {$this->ruleId} is inactive, skipping");
                return;
            }

            //LogHelper::rulesInfo("Processing rule {$rule->id} - Action: {$rule->action}");

            // Get trains that are visible in train grids (filtered by group configurations)
            $trains = $this->getRelevantTrains();

            foreach ($trains as $train) {
                $this->processRuleForTrain($rule, $train);
            }

        } catch (\Exception $e) {
            LogHelper::rulesError("Error processing train rule {$this->ruleId}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to trigger job retry mechanism
        } finally {
            // Ensure database connection is properly closed
            DB::disconnect();
        }
    }

    private function getRelevantTrains()
    {
        // Create a shared cache key for train data across all rule jobs (5-minute intervals)
        $interval = floor(now()->minute / 5) * 5;
        $cacheKey = 'shared_train_data_'.now()->format('Y-m-d_H:').str_pad($interval, 2, '0', STR_PAD_LEFT);

        // Cache train data for 5 minutes to prevent database storm
        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->fetchRelevantTrainsFromDatabase();
        });
    }

    private function fetchRelevantTrainsFromDatabase()
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
                if (! empty($selectedStations[$routeId])) {
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
            return collect();
        }

        // Get trains with minimal relationships to reduce memory usage
        return \App\Models\GtfsTrip::select([
            'gtfs_trips.trip_id',
            'gtfs_trips.route_id',
            'gtfs_trips.trip_short_name',
            'gtfs_trips.trip_headsign',
            'gtfs_trips.service_id',
        ])
            ->with(['currentStatus', 'stopTimes' => function ($query) {
                // Only get first and last stops to minimize data
                $query->select(['trip_id', 'stop_id', 'stop_sequence', 'arrival_time', 'departure_time'])
                    ->orderBy('stop_sequence');
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
                //LogHelper::rulesInfo("Train {$train->trip_id} has no configured stops, skipping");
                return;
            }

            //LogHelper::rulesInfo("Checking rule {$rule->id} for train {$train->trip_id} at " . $configuredStops->count() . " configured stops");

            $stopsToUpdate = [];

            // Check each configured stop individually
            foreach ($configuredStops as $stopTime) {
                // First check if this rule has already been executed for this train/stop combination
                try {
                    if (TrainRuleExecution::hasBeenExecuted($rule->id, $train->trip_id, $stopTime->stop_id)) {
                        //LogHelper::rulesInfo("Rule {$rule->id} already executed for train {$train->trip_id} at stop {$stopTime->stop_id}, skipping");
                        continue;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle the case where the rule might have been deleted
                    LogHelper::rulesDebug("Error checking rule execution for rule {$rule->id}: ".$e->getMessage());

                    continue;
                }

                $stopStatus = StopStatus::where('trip_id', $train->trip_id)
                    ->where('stop_id', $stopTime->stop_id)
                    ->first();
                $currentStatus = $stopStatus ? $stopStatus->status : 'On Time';

                // Check if rule condition is met for this specific stop
                $conditionMet = $this->shouldTriggerForStop($rule, $train, $stopTime->stop_id);

                //LogHelper::rulesInfo("Stop {$stopTime->stop_id}: status='{$currentStatus}', condition " . ($conditionMet ? 'MET' : 'NOT MET'));

                if ($conditionMet) {
                    $stopsToUpdate[] = $stopTime->stop_id;
                }
            }

            if (! empty($stopsToUpdate)) {
                //LogHelper::rulesInfo("Rule {$rule->id} condition met for train {$train->trip_id} at stops: " . implode(', ', $stopsToUpdate) . ", applying action: {$rule->action}");
                $this->applyActionToStops($rule, $train, $stopsToUpdate);
            } else {
                //LogHelper::rulesInfo("Rule {$rule->id} condition NOT met for train {$train->trip_id} at any configured stops");
            }
        } catch (\Exception $e) {
            LogHelper::rulesError("Error processing rule {$rule->id} for train {$train->trip_id}: ".$e->getMessage());
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
        $actions = $rule->getActions();

        foreach ($actions as $index => $action) {
            $actionValue = is_array($rule->action_value) && isset($rule->action_value[$index])
                ? $rule->action_value[$index]
                : $rule->action_value;

            if ($action === 'set_status') {
                $this->setTrainStatusAtStops($rule, $train, $stopIds, $actionValue);
            } elseif ($action === 'set_check_in_status') {
                $this->setCheckInStatus($rule, $train, $actionValue);
            } elseif ($action === 'make_announcement') {
                $this->makeAnnouncement($rule, $train, $actionValue);
            } elseif ($action === 'update_platform') {
                $this->updatePlatform($rule, $train, $stopIds, $actionValue);
            }
        }

        // Record the execution for each unique stop to prevent re-triggering
        $uniqueStopIds = array_unique($stopIds);
        foreach ($uniqueStopIds as $stopId) {
            $execution = TrainRuleExecution::recordExecution(
                $rule->id,
                $train->trip_id,
                $stopId,
                implode(',', $actions),
                [
                    'actions' => $actions,
                    'action_values' => $rule->action_value,
                    'train_number' => $train->trip_short_name ?? $train->trip_id,
                ]
            );

            // If recording failed due to deleted rule, exit early
            if ($execution === null) {
                LogHelper::rulesInfo("Rule {$rule->id} was deleted during execution, stopping further processing");

                return;
            }
        }
    }

    /**
     * Convert RGB color string to hex format
     */
    private function rgbToHex($rgb)
    {
        if (empty($rgb)) {
            return '#9CA3AF'; // Default gray color
        }

        $rgbArray = explode(',', $rgb);
        if (count($rgbArray) !== 3) {
            return '#9CA3AF'; // Default gray color if invalid format
        }

        $hex = '#';
        foreach ($rgbArray as $component) {
            $hex .= str_pad(dechex(trim($component)), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($hex);
    }

    private function setTrainStatusAtStops($rule, $train, $stopIds, $actionValue = null)
    {
        // Get the status text from the statuses table
        $statusId = $actionValue ?? $rule->action_value;
        $status = Status::find($statusId);
        if (! $status) {
            LogHelper::rulesError("Status with ID {$statusId} not found for rule {$rule->id}");

            return;
        }

        DB::beginTransaction();
        try {
            $updatedStops = [];

            foreach ($stopIds as $stopId) {
                // Check if status has already been set to prevent unnecessary updates
                $currentStopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                    ->where('stop_id', $stopId)
                    ->first();

                if ($currentStopStatus && $currentStopStatus->status === $status->status) {
                    continue; // Skip if already set
                }

                // Update or create the status in stop_statuses table with colors
                \App\Models\StopStatus::updateOrCreate(
                    [
                        'trip_id' => $train->trip_id,
                        'stop_id' => $stopId,
                    ],
                    [
                        'status' => $status->status,
                        'status_color' => $status->color_rgb ?? '156,163,175',
                        'status_color_hex' => $status->color_rgb ? $this->rgbToHex($status->color_rgb) : '#9CA3AF',
                    ]
                );

                $updatedStops[] = $stopId;
            }

            if (! empty($updatedStops)) {
                // Update or create the status in train_statuses table (for backward compatibility)
                TrainStatus::updateOrCreate(
                    ['trip_id' => $train->trip_id],
                    ['status' => $status->status]
                );

                DB::commit();

                //LogHelper::rulesInfo("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

                // Broadcast the status change event after successful commit
                event(new TrainStatusUpdated($train->trip_id, $status->status));
            } else {
                DB::commit();
                //LogHelper::rulesInfo("Train {$train->trip_id} already has status {$status->status} at specified stops, skipping");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::rulesError("Failed to update train status at stops for train {$train->trip_id}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function setCheckInStatus($rule, $train, $actionValue = null)
    {
        // Get the check-in status from the check_in_statuses table
        $checkInStatusId = $actionValue ?? $rule->action_value;
        $checkInStatus = CheckInStatus::find($checkInStatusId);
        if (! $checkInStatus) {
            LogHelper::rulesError("Check-in status with ID {$checkInStatusId} not found for rule {$rule->id}");

            return;
        }

        try {
            // Update or create the check-in status assignment
            TrainCheckInStatus::updateOrCreate(
                [
                    'trip_id' => $train->trip_id,
                ],
                [
                    'check_in_status_id' => $checkInStatus->id,
                ]
            );

            LogHelper::rulesInfo("Set check-in status '{$checkInStatus->status}' for train {$train->trip_id} via rule {$rule->id}");
        } catch (\Exception $e) {
            LogHelper::rulesError("Error setting check-in status for train {$train->trip_id}: ".$e->getMessage());
        }
    }

    private function updatePlatform($rule, $train, $stopIds, $actionValue)
    {
        if (! is_array($actionValue) || ! isset($actionValue['platform'])) {
            LogHelper::rulesError("Invalid platform action value for rule {$rule->id}");

            return;
        }

        $platform = $actionValue['platform'];

        DB::beginTransaction();
        try {
            foreach ($stopIds as $stopId) {
                StopStatus::updateOrCreate(
                    ['trip_id' => $train->trip_id, 'stop_id' => $stopId],
                    [
                        'departure_platform' => $platform,
                        'arrival_platform' => $platform,
                        'is_realtime_update' => true,
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();
            LogHelper::rulesInfo("Updated platform for train {$train->trip_id} to {$platform} at stops: ".implode(', ', $stopIds));

        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::rulesError("Failed to update platform for train {$train->trip_id}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function setTrainStatus($rule, $train)
    {
        // Get the status text from the statuses table
        $status = Status::find($rule->action_value);
        if (! $status) {
            LogHelper::rulesError("Status with ID {$rule->action_value} not found for rule {$rule->id}");

            return;
        }

        // Get all stops for this train that are configured in any group's train grid
        $allConfiguredStops = $this->getConfiguredStopsForTrain($train);

        if ($allConfiguredStops->isEmpty()) {
            LogHelper::rulesDebug("No configured stops found for train {$train->trip_id}");

            return;
        }

        DB::beginTransaction();
        try {
            $updatedStops = [];

            foreach ($allConfiguredStops as $stopTime) {
                // Check if status has already been set to prevent unnecessary updates
                $currentStopStatus = \App\Models\StopStatus::where('trip_id', $train->trip_id)
                    ->where('stop_id', $stopTime->stop_id)
                    ->first();

                if ($currentStopStatus && $currentStopStatus->status === $status->status) {
                    continue; // Skip if already set
                }

                // Update or create the status in stop_statuses table with colors
                \App\Models\StopStatus::updateOrCreate(
                    [
                        'trip_id' => $train->trip_id,
                        'stop_id' => $stopTime->stop_id,
                    ],
                    [
                        'status' => $status->status,
                        'status_color' => $status->color_rgb ?? '156,163,175',
                        'status_color_hex' => $status->color_rgb ? $this->rgbToHex($status->color_rgb) : '#9CA3AF',
                    ]
                );

                $updatedStops[] = $stopTime->stop_id;
            }

            if (! empty($updatedStops)) {
                // Update or create the status in train_statuses table (for backward compatibility)
                TrainStatus::updateOrCreate(
                    ['trip_id' => $train->trip_id],
                    ['status' => $status->status]
                );

                DB::commit();

                //LogHelper::rulesInfo("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

                // Broadcast the status change event after successful commit
                event(new TrainStatusUpdated($train->trip_id, $status->status));
            } else {
                DB::commit();
                //LogHelper::rulesInfo("Train {$train->trip_id} already has status {$status->status} at all configured stops, skipping");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::rulesError("Failed to update train status for train {$train->trip_id}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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

    /**
     * Resolve announcement zones based on the rule's zone strategy
     */
    private function resolveAnnouncementZones($announcementData, $train): array
    {
        $zoneStrategy = $announcementData['zone_strategy'] ?? 'specific_zone';

        if ($zoneStrategy === 'group_zones') {
            // Get zones from the train's group
            return $this->getTrainGroupZones($train);
        } else {
            // Use specific zone from rule
            $specificZone = $announcementData['zone'] ?? 'Terminal';

            return [$specificZone];
        }
    }

    /**
     * Get zones associated with the train's group
     */
    private function getTrainGroupZones($train): array
    {
        try {
            // Find the group that this train belongs to through route selection
            // Trains are associated with groups through their routes
            $groupSelection = DB::table('group_train_table_selections as gtts')
                ->join('groups as g', 'gtts.group_id', '=', 'g.id')
                ->where('gtts.route_id', $train->route_id)
                ->where('g.active', true)
                ->select('g.id', 'g.name')
                ->first();

            if ($groupSelection) {
                $group = Group::with('zones')->find($groupSelection->id);
                if ($group && $group->zones->isNotEmpty()) {
                    $zones = $group->zones->pluck('value')->toArray();
                    LogHelper::rulesInfo("Found group zones for train {$train->trip_id}", [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'zones' => $zones,
                    ]);

                    return $zones;
                }
            }

            // If no group found or no zones configured, return default zone
            LogHelper::rulesDebug("Could not determine group zones for train {$train->trip_id}, using default zone", [
                'train_id' => $train->trip_id,
                'route_id' => $train->route_id,
                'group_found' => $groupSelection ? true : false,
            ]);

            return ['Terminal']; // Default fallback

        } catch (\Exception $e) {
            LogHelper::rulesError("Error resolving group zones for train {$train->trip_id}: ".$e->getMessage());

            return ['Terminal']; // Default fallback
        }
    }

    private function makeAnnouncement($rule, $train)
    {
        $announcementData = json_decode($rule->action_value, true);

        if (! $announcementData || ! isset($announcementData['template_id'])) {
            LogHelper::rulesError("Invalid announcement data for rule {$rule->id}");

            return;
        }

        $template = AviavoxTemplate::find($announcementData['template_id']);
        if (! $template) {
            LogHelper::rulesError("Template {$announcementData['template_id']} not found for rule {$rule->id}");

            return;
        }

        // Use friendly_name if available, otherwise fall back to name
        $templateName = $template->friendly_name ?? $template->name;

        // Determine zones based on strategy
        $zones = $this->resolveAnnouncementZones($announcementData, $train);

        if (empty($zones)) {
            LogHelper::rulesDebug('No zones resolved for train rule announcement', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'zone_strategy' => $announcementData['zone_strategy'] ?? 'unknown',
            ]);

            return;
        }

        // Check if announcement was already made recently to prevent spam
        $recentAnnouncement = DB::table('announcements')
            ->where('message', $templateName)
            ->where('area', implode(',', $zones))
            ->where('created_at', '>', now()->subMinutes(5))
            ->exists();

        if ($recentAnnouncement) {
            LogHelper::rulesInfo("Recent announcement for template '{$templateName}' in zones '{$zones}' already exists, skipping");

            return;
        }

        // Create announcement record in the database
        $announcement = \App\Models\Announcement::create([
            'type' => 'audio',
            'message' => $templateName,
            'scheduled_time' => now()->format('H:i:s'),
            'author' => 'System (Train Rule)',
            'area' => implode(',', $zones),
            'status' => 'Pending',
            'recurrence' => null,
        ]);

        LogHelper::rulesInfo("Train rule announcement created for train {$train->trip_id} using template '{$templateName}' in zones '{$zones}'", [
            'rule_id' => $rule->id,
            'template_id' => $announcementData['template_id'],
            'zones' => $zones,
            'zone_strategy' => $announcementData['zone_strategy'] ?? 'unknown',
            'variables' => $announcementData['variables'] ?? [],
        ]);

        // Try to send to Aviavox if configured
        $settings = \App\Models\AviavoxSetting::first();
        if ($settings && $settings->ip_address && $settings->port) {
            try {
                $this->sendToAviavox($template, $announcementData, $settings, $rule, $train, $zones);
                $announcement->update(['status' => 'Finished']);
            } catch (\Exception $e) {
                $announcement->update(['status' => 'Failed']);

                LogHelper::rulesError('Failed to send train rule announcement to Aviavox', [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'template_name' => $templateName,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // No Aviavox settings configured - still log the announcement but mark as finished
            $announcement->update(['status' => 'Finished']);

            LogHelper::rulesInfo('Train rule announcement logged (Aviavox not configured)', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'template_name' => $templateName,
                'zones' => $zones,
            ]);
        }
    }

    /**
     * Send train rule announcement to Aviavox system
     */
    private function sendToAviavox($template, $announcementData, $settings, $rule, $train, $zones): void
    {
        // Generate XML from template and variables
        LogHelper::rulesInfo('About to generate XML for train rule', [
            'rule_id' => $rule->id,
            'template_id' => $template->id,
            'template_name' => $template->name,
        ]);

        $xml = $this->generateXmlForTrainRule($template, $announcementData, $train);

        LogHelper::rulesInfo('Generated XML for train rule', [
            'rule_id' => $rule->id,
            'template_id' => $template->id,
            'final_xml' => $xml,
        ]);

        LogHelper::rulesInfo('Train Rule Announcement - Starting Aviavox transmission', [
            'rule_id' => $rule->id,
            'train_id' => $train->trip_id,
            'train_number' => $train->trip_short_name ?? $train->trip_id,
            'template_name' => $template->friendly_name ?? $template->name,
            'aviavox_server' => $settings->ip_address.':'.$settings->port,
            'template_id' => $template->id,
            'zone' => implode(',', $zones),
            'xml_to_send' => $xml,
        ]);

        // Connect to Aviavox server using the same method as existing system
        $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 30);
        if (! $socket) {
            throw new \Exception("Failed to connect to Aviavox: $errstr ($errno)");
        }

        try {
            // Authentication flow (same as existing system)
            // Step 1: Send challenge request
            $challengeRequest = '<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>';
            LogHelper::rulesDebug('Train Rule Announcement - Sending challenge request', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'challenge_xml' => $challengeRequest,
            ]);
            fwrite($socket, chr(2).$challengeRequest.chr(3));

            // Step 2: Read challenge response
            $response = fread($socket, 1024);
            LogHelper::rulesDebug('Train Rule Announcement - Received challenge response', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'challenge_response' => $response,
            ]);

            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;

            if (! $challenge) {
                LogHelper::rulesError('Train Rule Announcement - Invalid challenge received', [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'response' => $response,
                ]);
                throw new \Exception('Invalid challenge received from Aviavox');
            }

            // Step 3: Generate password hash
            $password = $settings->password;
            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password.$salt.strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));

            LogHelper::rulesDebug('Train Rule Announcement - Authentication details', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'username' => $settings->username,
                'challenge' => $challenge,
                'password_length' => $passwordLength,
                'salt' => $salt,
            ]);

            // Step 4: Send authentication request
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            LogHelper::rulesDebug('Train Rule Announcement - Sending auth request', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'auth_xml' => $authRequest,
            ]);
            fwrite($socket, chr(2).$authRequest.chr(3));

            // Step 5: Read authentication response
            $authResponse = fread($socket, 1024);
            LogHelper::rulesDebug('Train Rule Announcement - Received auth response', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'auth_response' => $authResponse,
            ]);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                LogHelper::rulesError('Train Rule Announcement - Authentication failed', [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'auth_response' => $authResponse,
                ]);
                throw new \Exception('Authentication failed');
            }

            // Step 6: Send the announcement
            LogHelper::rulesInfo('Train Rule Announcement - Sending announcement XML to Aviavox', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'final_xml' => $xml,
            ]);
            fwrite($socket, chr(2).$xml.chr(3));

            // Step 7: Read final response
            $finalResponse = fread($socket, 1024);
            LogHelper::rulesInfo('Train Rule Announcement - Received final response from Aviavox', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'final_response' => $finalResponse,
                'xml_sent' => $xml,
            ]);

        } finally {
            fclose($socket);
            LogHelper::rulesDebug('Train Rule Announcement - Connection closed', [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
            ]);
        }
    }

    /**
     * Generate XML for train rule announcement
     */
    private function generateXmlForTrainRule($template, $announcementData, $train): string
    {
        LogHelper::rulesInfo('NEW XML GENERATION METHOD CALLED - Building XML from scratch', [
            'rule_id' => $announcementData['rule_id'] ?? 'unknown',
            'template_id' => $template->id,
            'template_name' => $template->name,
        ]);

        // Log the original template for debugging
        LogHelper::rulesDebug('Original XML template for rule', [
            'rule_id' => $announcementData['rule_id'] ?? 'unknown',
            'template_id' => $template->id,
            'original_xml' => $template->xml_template,
        ]);

        // Instead of trying to fix a potentially malformed template, let's build the XML from scratch
        // This ensures we always have properly formatted XML
        $xml = '<AIP>';
        $xml .= '<MessageID>AnnouncementTriggerRequest</MessageID>';
        $xml .= '<MessageData>';
        $xml .= '<AnnouncementData>';
        $xml .= '<Item ID="MessageName" Value="'.htmlspecialchars($template->name).'"/>';

        // Add train number if the template has train variables
        $variables = $announcementData['variables'] ?? [];
        $variableTypes = $announcementData['variable_types'] ?? [];

        $hasTrainVariable = false;
        foreach ($variables as $key => $value) {
            $variableType = $variableTypes[$key] ?? 'manual';
            if ($variableType === 'dynamic' && strpos($value, '{{DYNAMIC_') === 0) {
                $variableName = str_replace(['{{DYNAMIC_', '}}'], '', $value);
                if ($variableName === 'train' || $variableName === 'train_number') {
                    $hasTrainVariable = true;
                    $trainNumber = preg_replace('/[^0-9]/', '', $train->trip_short_name ?? $train->trip_id);
                    $xml .= '<Item ID="TrainNumber" Value="'.htmlspecialchars($trainNumber).'"/>';
                    break;
                }
            }
        }

        // If no dynamic train variable found, check if template has train-related variables
        if (! $hasTrainVariable) {
            // Check if the original template has any train-related items
            if (strpos($template->xml_template, 'TrainNumber') !== false ||
                strpos($template->xml_template, 'train') !== false) {
                $trainNumber = preg_replace('/[^0-9]/', '', $train->trip_short_name ?? $train->trip_id);
                $xml .= '<Item ID="TrainNumber" Value="'.htmlspecialchars($trainNumber).'"/>';
            }
        }

        // Add zone
        $zone = ! empty($announcementData['zone']) ? $announcementData['zone'] : 'Terminal';
        $xml .= '<Item ID="Zones" Value="'.htmlspecialchars($zone).'"/>';

        // Add any other manual variables
        foreach ($variables as $key => $value) {
            $variableType = $variableTypes[$key] ?? 'manual';
            if ($variableType === 'manual' && $key !== 'zone') {
                $xml .= '<Item ID="'.htmlspecialchars($key).'" Value="'.htmlspecialchars($value).'"/>';
            }
        }

        $xml .= '</AnnouncementData>';
        $xml .= '</MessageData>';
        $xml .= '</AIP>';

        LogHelper::rulesDebug('Generated XML for rule', [
            'rule_id' => $announcementData['rule_id'] ?? 'unknown',
            'template_id' => $template->id,
            'generated_xml' => $xml,
        ]);

        return $xml;
    }

    /**
     * Process announcement variables, replacing dynamic markers with actual train data
     */
    private function processAnnouncementVariables($announcementData, $train): array
    {
        $processedVariables = [];
        $variables = $announcementData['variables'] ?? [];
        $variableTypes = $announcementData['variable_types'] ?? [];

        foreach ($variables as $key => $value) {
            $variableType = $variableTypes[$key] ?? 'manual';

            if ($variableType === 'dynamic' && strpos($value, '{{DYNAMIC_') === 0) {
                // Extract the variable name from the marker
                $variableName = str_replace(['{{DYNAMIC_', '}}'], '', $value);
                $processedVariables[$key] = $this->getDynamicVariableValue($variableName, $train);
            } else {
                // Use the manual value as-is
                $processedVariables[$key] = $value;
            }
        }

        return $processedVariables;
    }

    /**
     * Get dynamic variable value based on train data
     */
    private function getDynamicVariableValue($variableName, $train): string
    {
        switch ($variableName) {
            case 'train_number':
                // Use the new train_number attribute that parses from trip_id
                return $train->train_number ?? preg_replace('/[^0-9]/', '', $train->trip_short_name ?? $train->trip_id);

            case 'train_date':
                // Return the formatted date from trip_id
                return $train->formatted_date ?? '';

            case 'train_date_human':
                // Return the human-readable date
                return $train->human_readable_date ?? '';

            case 'train_id':
                return $train->trip_id;

            case 'trip_headsign':
            case 'destination':
                return $train->trip_headsign ?? '';

            case 'route_short_name':
                return $train->route_short_name ?? '';

            case 'route_long_name':
                return $train->route_long_name ?? '';

            case 'departure_time':
                // Get the departure time for the first configured stop
                try {
                    $stopTime = \App\Models\GtfsStopTime::where('trip_id', $train->trip_id)
                        ->orderBy('stop_sequence')
                        ->first();

                    return $stopTime ? substr($stopTime->departure_time, 0, 5) : '';
                } catch (\Exception $e) {
                    return '';
                }

            case 'platform':
                // Get platform information if available
                try {
                    $stopTime = \App\Models\GtfsStopTime::where('trip_id', $train->trip_id)
                        ->join('gtfs_stops', 'gtfs_stop_times.stop_id', '=', 'gtfs_stops.stop_id')
                        ->whereNotNull('gtfs_stops.platform_code')
                        ->orderBy('stop_sequence')
                        ->first();

                    return $stopTime ? $stopTime->platform_code : '';
                } catch (\Exception $e) {
                    return '';
                }

            case 'current_time':
                return now()->format('H:i');

            case 'current_date':
                return now()->format('Y-m-d');

            default:
                // For unknown variables, try to get them from the train object
                return $train->$variableName ?? '';
        }
    }

    /**
     * Clean up and validate XML to ensure it's properly formatted
     */
    private function cleanupXml($xml, $train): string
    {
        $trainNumber = preg_replace('/[^0-9]/', '', $train->trip_short_name ?? $train->trip_id);

        // Log the XML before cleanup for debugging
        LogHelper::rulesDebug('XML before cleanup', [
            'xml' => $xml,
            'train_number' => $trainNumber,
        ]);

        // Remove any malformed train number entries completely - more aggressive patterns
        $xml = preg_replace(
            '/<Item\s+ID="[^"]*[Tt]rain[^"]*"\s+Value="[^"]*[0-9]+[^"]*NLAMA[^"]*"[^>]*\/>/i',
            '',
            $xml
        );

        // Remove any broken XML lines that might contain partial train numbers
        $xml = preg_replace(
            '/\s*[0-9]+\s+NLAMA[^>]*\s*\/>/i',
            '',
            $xml
        );

        // Remove any lines containing "NLAMA" and "&gt;" (HTML encoded >)
        $xml = preg_replace(
            '/\s*[0-9]+\s+NLAMA[^>]*&gt;[^>]*\s*\/>/i',
            '',
            $xml
        );

        // Remove any malformed Item elements that don't have proper closing
        $xml = preg_replace(
            '/<Item\s+[^>]*[0-9]+[^>]*NLAMA[^>]*\s*\/>/i',
            '',
            $xml
        );

        // Remove any lines that start with a number followed by NLAMA
        $xml = preg_replace(
            '/\s*[0-9]+\s+NLAMA[^>]*\s*\/>/i',
            '',
            $xml
        );

        // Remove the specific malformed pattern we're seeing in the logs
        $xml = preg_replace(
            '/\s*[0-9]+\s+NLAMA[^>]*&gt;[^>]*\s*\"\s*\/>/i',
            '',
            $xml
        );

        // Remove any lines containing the exact pattern from the logs
        $xml = preg_replace(
            '/\s*[0-9]+\s+NLAMA[^>]*-&gt;[^>]*\s*\/>/i',
            '',
            $xml
        );

        // Ensure we have a proper TrainNumber entry
        if (strpos($xml, 'TrainNumber') === false && ! empty($trainNumber)) {
            // Find the position after MessageName and insert TrainNumber
            $xml = preg_replace(
                '/(<Item\s+ID="MessageName"[^>]*\/>)/',
                '$1'."\n".'<Item ID="TrainNumber" Value="'.htmlspecialchars($trainNumber).'"/>',
                $xml
            );
        }

        // Clean up any extra whitespace and ensure proper formatting
        $xml = preg_replace('/>\s+</', '><', $xml);
        $xml = preg_replace('/\s+/', ' ', $xml);

        // Log the XML after cleanup for debugging
        LogHelper::rulesDebug('XML after cleanup', [
            'xml' => $xml,
            'train_number' => $trainNumber,
        ]);

        return $xml;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        LogHelper::rulesError("Train rule job failed for rule {$this->ruleId}: ".$exception->getMessage(), [
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
