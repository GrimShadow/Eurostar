<?php

namespace App\Jobs;

use App\Models\TrainRule;
use App\Models\GtfsTrip;
use App\Models\Status;
use App\Models\AviavoxTemplate;
use App\Models\TrainStatus;
use App\Models\StopStatus;
use App\Models\Group;
use App\Models\TrainRuleExecution;
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
            
            if (!$rule) {
                Log::info("Rule {$this->ruleId} not found (possibly deleted), skipping job execution");
                return;
            }
            
            if (!$rule->is_active) {
                //Log::info("Rule {$this->ruleId} is inactive, skipping");
                return;
            }

            //Log::info("Processing rule {$rule->id} - Action: {$rule->action}");


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
                // First check if this rule has already been executed for this train/stop combination
                try {
                    if (TrainRuleExecution::hasBeenExecuted($rule->id, $train->trip_id, $stopTime->stop_id)) {
                        //Log::info("Rule {$rule->id} already executed for train {$train->trip_id} at stop {$stopTime->stop_id}, skipping");
                        continue;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle the case where the rule might have been deleted
                    Log::warning("Error checking rule execution for rule {$rule->id}: " . $e->getMessage());
                    continue;
                }
                
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
        
        // Record the execution for each unique stop to prevent re-triggering
        $uniqueStopIds = array_unique($stopIds);
        foreach ($uniqueStopIds as $stopId) {
            $execution = TrainRuleExecution::recordExecution(
                $rule->id,
                $train->trip_id,
                $stopId,
                $rule->action,
                [
                    'action_value' => $rule->action_value,
                    'train_number' => $train->trip_short_name ?? $train->trip_id
                ]
            );
            
            // If recording failed due to deleted rule, exit early
            if ($execution === null) {
                Log::info("Rule {$rule->id} was deleted during execution, stopping further processing");
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

    private function setTrainStatusAtStops($rule, $train, $stopIds)
    {
        // Get the status text from the statuses table
        $status = Status::find($rule->action_value);
        if (!$status) {
            Log::error("Status with ID {$rule->action_value} not found for rule {$rule->id}");
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
                        'stop_id' => $stopId
                    ],
                    [
                        'status' => $status->status,
                        'status_color' => $status->color_rgb ?? '156,163,175',
                        'status_color_hex' => $status->color_rgb ? $this->rgbToHex($status->color_rgb) : '#9CA3AF',
                    ]
                );
                
                $updatedStops[] = $stopId;
            }

            if (!empty($updatedStops)) {
                // Update or create the status in train_statuses table (for backward compatibility)
                TrainStatus::updateOrCreate(
                    ['trip_id' => $train->trip_id],
                    ['status' => $status->status]
                );

                DB::commit();

                //Log::info("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

                // Broadcast the status change event after successful commit
                event(new TrainStatusUpdated($train->trip_id, $status->status));
            } else {
                DB::commit();
                //Log::info("Train {$train->trip_id} already has status {$status->status} at specified stops, skipping");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update train status at stops for train {$train->trip_id}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
                        'stop_id' => $stopTime->stop_id
                    ],
                    [
                        'status' => $status->status,
                        'status_color' => $status->color_rgb ?? '156,163,175',
                        'status_color_hex' => $status->color_rgb ? $this->rgbToHex($status->color_rgb) : '#9CA3AF',
                    ]
                );
                
                $updatedStops[] = $stopTime->stop_id;
            }

            if (!empty($updatedStops)) {
                // Update or create the status in train_statuses table (for backward compatibility)
                TrainStatus::updateOrCreate(
                    ['trip_id' => $train->trip_id],
                    ['status' => $status->status]
                );

                DB::commit();

                //Log::info("Set status for train {$train->trip_id} to {$status->status} at stops: " . implode(', ', $updatedStops));

                // Broadcast the status change event after successful commit
                event(new TrainStatusUpdated($train->trip_id, $status->status));
            } else {
                DB::commit();
                //Log::info("Train {$train->trip_id} already has status {$status->status} at all configured stops, skipping");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update train status for train {$train->trip_id}", [
                'rule_id' => $rule->id,
                'error' => $e->getMessage()
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

        // Use friendly_name if available, otherwise fall back to name
        $templateName = $template->friendly_name ?? $template->name;
        $zone = $announcementData['zone'] ?? 'Terminal';

        // Check if announcement was already made recently to prevent spam
        $recentAnnouncement = DB::table('announcements')
            ->where('message', $templateName)
            ->where('area', $zone)
            ->where('created_at', '>', now()->subMinutes(5))
            ->exists();

        if ($recentAnnouncement) {
            Log::info("Recent announcement for template '{$templateName}' in zone '{$zone}' already exists, skipping");
            return;
        }

        // Create announcement record in the database
        $announcement = \App\Models\Announcement::create([
            'type' => 'audio',
            'message' => $templateName,
            'scheduled_time' => now()->format('H:i:s'),
            'author' => 'System (Train Rule)',
            'area' => $zone,
            'status' => 'Pending',
            'recurrence' => null
        ]);

        Log::info("Train rule announcement created for train {$train->trip_id} using template '{$templateName}' in zone '{$zone}'", [
            'rule_id' => $rule->id,
            'template_id' => $announcementData['template_id'],
            'zone' => $zone,
            'variables' => $announcementData['variables'] ?? []
        ]);

        // Try to send to Aviavox if configured
        $settings = \App\Models\AviavoxSetting::first();
        if ($settings && $settings->ip_address && $settings->port) {
            try {
                $this->sendToAviavox($template, $announcementData, $settings, $rule, $train);
                $announcement->update(['status' => 'Finished']);
            } catch (\Exception $e) {
                $announcement->update(['status' => 'Failed']);
                
                Log::error("Failed to send train rule announcement to Aviavox", [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'template_name' => $templateName,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // No Aviavox settings configured - still log the announcement but mark as finished
            $announcement->update(['status' => 'Finished']);
            
            Log::info("Train rule announcement logged (Aviavox not configured)", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'template_name' => $templateName,
                'zone' => $zone
            ]);
        }
    }

    /**
     * Send train rule announcement to Aviavox system
     */
    private function sendToAviavox($template, $announcementData, $settings, $rule, $train): void
    {
        // Generate XML from template and variables
        $xml = $this->generateXmlForTrainRule($template, $announcementData, $train);
        
        Log::info("Train Rule Announcement - Starting Aviavox transmission", [
            'rule_id' => $rule->id,
            'train_id' => $train->trip_id,
            'train_number' => $train->trip_short_name ?? $train->trip_id,
            'template_name' => $template->friendly_name ?? $template->name,
            'aviavox_server' => $settings->ip_address . ':' . $settings->port,
            'template_id' => $template->id,
            'zone' => $announcementData['zone'] ?? 'Terminal',
            'xml_to_send' => $xml
        ]);
        
        // Connect to Aviavox server using the same method as existing system
        $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 30);
        if (!$socket) {
            throw new \Exception("Failed to connect to Aviavox: $errstr ($errno)");
        }

        try {
            // Authentication flow (same as existing system)
            // Step 1: Send challenge request
            $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            Log::debug("Train Rule Announcement - Sending challenge request", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'challenge_xml' => $challengeRequest
            ]);
            fwrite($socket, chr(2) . $challengeRequest . chr(3));
            
            // Step 2: Read challenge response
            $response = fread($socket, 1024);
            Log::debug("Train Rule Announcement - Received challenge response", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'challenge_response' => $response
            ]);
            
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;
            
            if (!$challenge) {
                Log::error("Train Rule Announcement - Invalid challenge received", [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'response' => $response
                ]);
                throw new \Exception('Invalid challenge received from Aviavox');
            }

            // Step 3: Generate password hash
            $password = $settings->password;
            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password . $salt . strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));
            
            Log::debug("Train Rule Announcement - Authentication details", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'username' => $settings->username,
                'challenge' => $challenge,
                'password_length' => $passwordLength,
                'salt' => $salt
            ]);

            // Step 4: Send authentication request
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            Log::debug("Train Rule Announcement - Sending auth request", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'auth_xml' => $authRequest
            ]);
            fwrite($socket, chr(2) . $authRequest . chr(3));

            // Step 5: Read authentication response
            $authResponse = fread($socket, 1024);
            Log::debug("Train Rule Announcement - Received auth response", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'auth_response' => $authResponse
            ]);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                Log::error("Train Rule Announcement - Authentication failed", [
                    'rule_id' => $rule->id,
                    'train_id' => $train->trip_id,
                    'auth_response' => $authResponse
                ]);
                throw new \Exception('Authentication failed');
            }

            // Step 6: Send the announcement
            Log::info("Train Rule Announcement - Sending announcement XML to Aviavox", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'final_xml' => $xml
            ]);
            fwrite($socket, chr(2) . $xml . chr(3));
            
            // Step 7: Read final response
            $finalResponse = fread($socket, 1024);
            Log::info("Train Rule Announcement - Received final response from Aviavox", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id,
                'final_response' => $finalResponse,
                'xml_sent' => $xml
            ]);

        } finally {
            fclose($socket);
            Log::debug("Train Rule Announcement - Connection closed", [
                'rule_id' => $rule->id,
                'train_id' => $train->trip_id
            ]);
        }
    }

    /**
     * Generate XML for train rule announcement
     */
    private function generateXmlForTrainRule($template, $announcementData, $train): string
    {
        // Start with the template's XML content
        $xml = $template->xml_template;
        
        // If no XML template, fall back to basic format
        if (empty($xml)) {
            $xml = '<AIP>
                <MessageID>AnnouncementTriggerRequest</MessageID>
                <MessageData>
                    <AnnouncementData>
                        <Item ID="MessageName" Value="{MessageName}"/>
                        <Item ID="Zones" Value="{zone}"/>
                    </AnnouncementData>
                </MessageData>
            </AIP>';
        }

        // Prepare variables for substitution
        $variables = array_merge($announcementData['variables'] ?? [], [
            'MessageName' => $template->name,
            'zone' => $announcementData['zone'] ?? 'Terminal',
            'train_number' => $train->trip_short_name ?? $train->trip_id,
            'train_id' => $train->trip_id,
            'trip_headsign' => $train->trip_headsign ?? ''
        ]);

        // Replace variables in the XML template
        foreach ($variables as $key => $value) {
            $xml = str_replace('{' . $key . '}', htmlspecialchars($value), $xml);
        }

        // Clean up any remaining unreplaced variables (remove empty {variable} placeholders)
        $xml = preg_replace('/\{[^}]+\}/', '', $xml);
        
        // Clean up whitespace and format XML properly
        $xml = preg_replace('/>\s+</', '><', trim($xml));

        return $xml;
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