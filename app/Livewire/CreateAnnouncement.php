<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AviavoxSetting;
use App\Models\AviavoxTemplate;
use App\Models\Group;
use App\Models\GtfsTrip;
use App\Models\Reason;
use App\Models\Zone;
use App\Services\LogHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateAnnouncement extends Component
{
    public $type = 'audio';

    public $selectedAnnouncement = '';

    public $variables = [];

    public $zones;

    public $trains = [];

    public $selectedZone = '';

    public $selectedTrain = '';

    public $scheduledTime = '';

    public $selectedRoute = '';

    public $textInput = '';

    public $selectedReason = '';

    public $templates = [];

    public $reasons = [];

    public $group = null;

    public $selectedStations = [];

    public function mount(?Group $group = null)
    {
        $this->group = $group;
        $this->zones = Zone::orderBy('value')->get();
        $this->reasons = Reason::orderBy('code')->get();

        // Load selected stations if we have a group
        if ($this->group) {
            $this->loadSelectedStations();
        }

        $this->loadTrains();
        $this->templates = AviavoxTemplate::all()->mapWithKeys(function ($template) {
            return [$template->name => [
                'friendly_name' => $template->friendly_name ?? $template->name,
                'variables' => $template->variables,
                'xml_template' => $template->xml_template,
            ]];
        })->toArray();
    }

    public function loadSelectedStations()
    {
        if (! $this->group) {
            $this->selectedStations = [];

            return;
        }

        $this->selectedStations = $this->group->routeStations()
            ->where('is_active', true)
            ->get()
            ->groupBy('route_id')
            ->map(function ($stations) {
                return $stations->pluck('stop_id')->toArray();
            })
            ->toArray();
    }

    private function loadTrains()
    {
        if (! $this->group) {
            // Fallback to original logic if no group (for backwards compatibility)
            $this->loadTrainsOriginal();

            return;
        }

        // Create a cache key based on group and current time (rounded to nearest minute)
        $cacheKey = "create_announcement_trains_group_{$this->group->id}_".now()->format('Y-m-d_H:i');

        // Cache the expensive query result for 5 minutes
        $this->trains = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getTrainsData();
        });
    }

    private function getTrainsData()
    {
        try {
            // Get both API routes and group-specific routes (same logic as TrainGrid)
            $apiRoutes = DB::table('selected_routes')
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            $groupRoutes = $this->group->selectedRoutes()
                ->where('is_active', true)
                ->pluck('route_id')
                ->toArray();

            // Combine both sets of routes
            $selectedRoutes = array_unique(array_merge($apiRoutes, $groupRoutes));

            if (empty($selectedRoutes)) {
                return [];
            }

            // Set time range - show trains from 30 minutes ago to end of day
            $currentTime = now()->subMinutes(30)->format('H:i:s');
            $endTime = '23:59:59';

            // Get unique trips for today that are visible in the group's train grid
            $uniqueTrips = DB::table('gtfs_trips')
                ->select([
                    'gtfs_trips.trip_id',
                    'gtfs_trips.route_id',
                    'gtfs_trips.trip_short_name',
                    'gtfs_trips.trip_headsign',
                ])
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
                ->groupBy('gtfs_trips.trip_id', 'gtfs_trips.route_id', 'gtfs_trips.trip_short_name', 'gtfs_trips.trip_headsign')
                ->orderBy('gtfs_trips.trip_short_name')
                ->limit(20) // Limit to prevent excessive data loading
                ->get();

            $trains = [];

            foreach ($uniqueTrips as $uniqueTrip) {
                // Only include trains that have stops in the group's selected stations
                $hasValidStops = DB::table('gtfs_stop_times')
                    ->where('gtfs_stop_times.trip_id', $uniqueTrip->trip_id)
                    ->whereIn('gtfs_stop_times.stop_id', $this->selectedStations[$uniqueTrip->route_id] ?? [])
                    ->exists();

                if (! $hasValidStops) {
                    continue;
                }

                // Get the first stop for this trip in the selected stations
                $firstStop = DB::table('gtfs_stop_times')
                    ->where('gtfs_stop_times.trip_id', $uniqueTrip->trip_id)
                    ->whereIn('gtfs_stop_times.stop_id', $this->selectedStations[$uniqueTrip->route_id] ?? [])
                    ->orderBy('gtfs_stop_times.stop_sequence')
                    ->first();

                if ($firstStop) {
                    // Use the parsed train number from trip_id
                    $trainNumber = $uniqueTrip->train_number ?? $uniqueTrip->trip_short_name;

                    $trains[] = [
                        'number' => $trainNumber,
                        'trip_id' => $uniqueTrip->trip_id,
                        'departure' => substr($firstStop->departure_time, 0, 5),
                        'route_id' => $uniqueTrip->route_id,
                    ];
                }
            }

            // Sort trains by departure time and limit to next 6 trains
            usort($trains, function ($a, $b) {
                return strtotime($a['departure']) - strtotime($b['departure']);
            });

            return array_slice($trains, 0, 6);

        } catch (\Exception $e) {
            LogHelper::announcementError('CreateAnnouncement - Error loading trains:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    private function loadTrainsOriginal()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->whereIn('gtfs_trips.route_id', function ($query) {
                $query->select('route_id')
                    ->from('selected_routes')
                    ->where('is_active', true);
            })
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->select([
                'gtfs_trips.trip_headsign as number',
                'gtfs_trips.trip_id',
                'gtfs_stop_times.departure_time as departure',
                'gtfs_routes.route_long_name',
                'gtfs_trips.trip_headsign as destination',
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->limit(6)
            ->get()
            ->map(function ($train) {
                // Extract just the train number
                $trainNumber = $train->number;
                if (preg_match('/^(\d+)/', $trainNumber, $matches)) {
                    $trainNumber = $matches[1];
                } elseif (strpos($trainNumber, ' ') !== false) {
                    $trainNumber = explode(' ', $trainNumber)[0];
                }

                return [
                    'number' => $trainNumber,
                    'departure' => substr($train->departure, 0, 5),
                    'route_name' => $train->route_long_name,
                    'destination' => $train->destination,
                ];
            })
            ->toArray();

        $this->trains = $trains;
    }

    public function updatedSelectedAnnouncement($value)
    {
        if (isset($this->templates[$value])) {
            $this->variables = $this->templates[$value]['variables'];
        }
    }

    public function generateXml()
    {
        $template = $this->templates[$this->selectedAnnouncement];
        $xml = "<AIP>\n";
        $xml .= "\t<MessageID>AnnouncementTriggerRequest</MessageID>\n";
        $xml .= "\t<MessageData>\n";
        $xml .= "\t\t<AnnouncementData>\n";
        $xml .= "\t\t\t<Item ID=\"MessageName\" Value=\"{$this->selectedAnnouncement}\"/>\n";

        foreach ($template['variables'] as $id => $type) {
            $value = $this->getVariableValue($id, $type);
            $xml .= "\t\t\t<Item ID=\"{$id}\" Value=\"{$value}\"/>\n";

        }

        $xml .= "\t\t</AnnouncementData>\n";
        $xml .= "\t</MessageData>\n";
        $xml .= '</AIP>';

        return $xml;
    }

    private function getVariableValue($id, $type)
    {
        switch ($type) {
            case 'zone':
                return $this->selectedZone;
            case 'train':
                // Return the selected train number directly
                return $this->selectedTrain ?: '';
            case 'datetime':
                if ($this->scheduledTime) {
                    // Parse the time in Amsterdam timezone (CET/CEST)
                    $localTime = Carbon::parse($this->scheduledTime, 'Europe/Amsterdam');
                    // Get the current timezone offset (UTC+1 or UTC+2)
                    $offset = $localTime->format('P'); // This will give us +01:00 or +02:00

                    return $localTime->format('Y-m-d\TH:i:s').$offset;
                }

                return '';
            case 'route':
                return $this->selectedRoute ?? 'GBR_LON'; // Default to London
            case 'text':
                return $this->textInput;
            case 'reason':
                return $this->selectedReason;
            default:
                return '';
        }
    }

    public function authenticateAndSendMessage($server, $port, $username, $password, $xml)
    {
        LogHelper::announcementInfo('Manual Announcement - Starting Aviavox transmission', [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aviavox_server' => $server.':'.$port,
            'selected_announcement' => $this->selectedAnnouncement,
            'zone' => $this->selectedZone,
            'xml_to_send' => $xml,
        ]);

        // Step 1: Create a TCP socket to connect to the server
        $socket = fsockopen($server, $port, $errno, $errstr, 30);
        if (! $socket) {
            LogHelper::announcementError('Manual Announcement - Failed to create socket', [
                'user_id' => Auth::id(),
                'server' => $server,
                'port' => $port,
                'error' => "$errstr ($errno)",
            ]);
            throw new \Exception("Failed to connect to server: $errstr ($errno)");
        }

        LogHelper::announcementDebug('Manual Announcement - Socket connected successfully', [
            'user_id' => Auth::id(),
            'server' => $server.':'.$port,
        ]);

        // Set stream timeout
        stream_set_timeout($socket, 20);

        try {
            // Step 2: Send AuthenticationChallengeRequest
            $challengeRequest = '<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>';
            LogHelper::announcementDebug('Manual Announcement - Sending challenge request', [
                'user_id' => Auth::id(),
                'challenge_xml' => $challengeRequest,
            ]);
            fwrite($socket, chr(2).$challengeRequest.chr(3));

            // Step 3: Read AuthenticationChallengeResponse
            $response = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                LogHelper::announcementError('Manual Announcement - Stream timed out while waiting for AuthenticationChallengeResponse', [
                    'user_id' => Auth::id(),
                ]);
                throw new \Exception('Stream timed out while waiting for response');
            }

            LogHelper::announcementDebug('Manual Announcement - Received challenge response', [
                'user_id' => Auth::id(),
                'challenge_response' => $response,
            ]);

            // Step 4: Extract challenge from response and generate password hash
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;
            if (! $challenge) {
                LogHelper::announcementError('Manual Announcement - Invalid challenge received', [
                    'user_id' => Auth::id(),
                    'response' => $response,
                ]);
                throw new \Exception('Invalid challenge received from server');
            }

            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password.$salt.strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));

            LogHelper::announcementDebug('Manual Announcement - Authentication details', [
                'user_id' => Auth::id(),
                'username' => $username,
                'challenge' => $challenge,
                'password_length' => $passwordLength,
                'salt' => $salt,
            ]);

            // Step 5: Send AuthenticationRequest
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            LogHelper::announcementDebug('Manual Announcement - Sending auth request', [
                'user_id' => Auth::id(),
                'auth_xml' => $authRequest,
            ]);
            fwrite($socket, chr(2).$authRequest.chr(3));

            // Step 5: Read AuthenticationResponse
            $authResponse = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                LogHelper::announcementError('Stream timed out while waiting for AuthenticationResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }

            LogHelper::announcementDebug('Manual Announcement - Received auth response', [
                'user_id' => Auth::id(),
                'auth_response' => $authResponse,
            ]);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                LogHelper::announcementError('Manual Announcement - Authentication failed', [
                    'user_id' => Auth::id(),
                    'auth_response' => $authResponse,
                ]);
                throw new \Exception('Authentication failed.');
            }

            LogHelper::announcementInfo('Manual Announcement - Authentication successful, sending announcement', [
                'user_id' => Auth::id(),
            ]);

            // Step 7: Send the XML announcement message
            LogHelper::announcementInfo('Manual Announcement - Sending announcement XML to Aviavox', [
                'user_id' => Auth::id(),
                'final_xml' => $xml,
            ]);
            fwrite($socket, chr(2).$xml.chr(3));

            // Step 8: Read the final response
            $finalResponse = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                LogHelper::announcementError('Stream timed out while waiting for AnnouncementTriggerResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }

            LogHelper::announcementInfo('Manual Announcement - Received final response from Aviavox', [
                'user_id' => Auth::id(),
                'final_response' => $finalResponse,
                'xml_sent' => $xml,
            ]);

            fclose($socket);

        } catch (\Exception $e) {
            LogHelper::announcementError('Error during AviaVox communication', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to send announcement: '.$e->getMessage());
        }
    }

    public function save()
    {
        $settings = AviavoxSetting::first();
        if (! $settings) {
            session()->flash('error', 'Aviavox settings not configured');

            return;
        }

        try {
            // Generate XML from template
            $xml = $this->generateXml();

            // Send the announcement via TCP
            $this->authenticateAndSendMessage(
                $settings->ip_address,
                $settings->port,
                $settings->username,
                $settings->password,
                $xml
            );

            // Create announcement record with all required fields
            Announcement::create([
                'type' => 'audio',
                'message' => $this->selectedAnnouncement,
                'scheduled_time' => $this->scheduledTime ? Carbon::parse($this->scheduledTime)->format('H:i:s') : Carbon::now()->format('H:i:s'),
                'author' => Auth::user()->name,
                'area' => $this->selectedZone ?? 'Terminal',
                'status' => 'Finished',
                'recurrence' => null, // No recurrence for immediate announcements
            ]);

            // Close the modal immediately
            $this->dispatch('close-modal');

            // Trigger the announcement banner
            $this->dispatch('showAnnouncementBanner');

            // Dispatch the announcement-sent event
            $this->dispatch('announcement-sent');

            session()->flash('success', 'Announcement sent successfully');

            // Schedule the page refresh to happen after a short delay
            $this->js('setTimeout(() => window.location.reload(), 100)');

        } catch (\Exception $e) {
            LogHelper::announcementError('Failed to send announcement: '.$e->getMessage());
            session()->flash('error', 'Failed to send announcement: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.create-announcement');
    }

    private function extractChallengeFromResponse($response)
    {
        preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);

        return $matches[1] ?? null;
    }
}
