<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AviavoxAnnouncement;
use App\Models\AviavoxSetting;
use App\Models\AviavoxTemplate;
use App\Models\Zone;
use App\Models\GtfsTrip;
use App\Models\PendingAnnouncement;
use App\Models\Reason;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    public function mount()
    {
        $this->zones = Zone::orderBy('value')->get();
        $this->reasons = Reason::orderBy('code')->get();
        $this->loadTrains();
        $this->templates = AviavoxTemplate::all()->mapWithKeys(function ($template) {
            return [$template->name => [
                'friendly_name' => $template->friendly_name ?? $template->name,
                'variables' => $template->variables,
                'xml_template' => $template->xml_template
            ]];
        })->toArray();
    }

    private function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->join('gtfs_routes', 'gtfs_trips.route_id', '=', 'gtfs_routes.route_id')
            ->leftJoin('train_statuses', 'gtfs_trips.trip_id', '=', 'train_statuses.trip_id')
            ->whereIn('gtfs_trips.route_id', function($query) {
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
                'gtfs_trips.trip_headsign as destination'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->limit(6)
            ->get()
            ->map(function ($train) {
                return [
                    'number' => $train->number,
                    'departure' => substr($train->departure, 0, 5),
                    'route_name' => $train->route_long_name,
                    'destination' => $train->destination
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
        $xml .= "</AIP>";

        return $xml;
    }

    private function getVariableValue($id, $type)
    {
        switch ($type) {
            case 'zone':
                return $this->selectedZone;
            case 'train':
                $train = GtfsTrip::where('trip_headsign', $this->selectedTrain)->first();
                return $train ? $train->trip_headsign : '';
            case 'datetime':
                if ($this->scheduledTime) {
                    // Parse the time in Amsterdam timezone (CET/CEST)
                    $localTime = Carbon::parse($this->scheduledTime, 'Europe/Amsterdam');
                    // Get the current timezone offset (UTC+1 or UTC+2)
                    $offset = $localTime->format('P'); // This will give us +01:00 or +02:00
                    return $localTime->format('Y-m-d\TH:i:s') . $offset;
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
        try {
            Log::info('Establishing TCP connection to AviaVox server', ['server' => $server, 'port' => $port]);
            $socket = fsockopen($server, $port, $errno, $errstr, 5);
            if (!$socket) {
                throw new \Exception("Failed to connect: $errstr ($errno)");
            }
            Log::info('TCP connection established successfully');

            // Set a read timeout for the socket
            stream_set_timeout($socket, 20); // 20 seconds timeout

            // Step 1: Send AuthenticationChallengeRequest
            $authChallengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            fwrite($socket, chr(2) . $authChallengeRequest . chr(3));
            Log::info('Sent AuthenticationChallengeRequest', ['request' => $authChallengeRequest]);

            // Step 2: Read AuthenticationChallengeResponse
            $response = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                Log::error('Stream timed out while waiting for AuthenticationChallengeResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }
            Log::info('Received AuthenticationChallengeResponse', ['response' => $response]);

            // Extract challenge
            $challenge = $this->extractChallengeFromResponse($response);
            if (!$challenge) {
                throw new \Exception('Challenge extraction failed from response.');
            }
            Log::info('Challenge code extracted successfully', ['challenge' => $challenge]);

            // Step 3: Hash the password
            $saltedPassword = $password . ($challenge ^ strlen($password)) . strrev($password);
            $passwordHash = strtoupper(hash('sha512', $saltedPassword));
            Log::info('Password hashed successfully', ['passwordHash' => $passwordHash]);

            // Step 4: Send AuthenticationRequest
            $authRequest = "<AIP>
                                <MessageID>AuthenticationRequest</MessageID>
                                <ClientID>1234567</ClientID>
                                <MessageData>
                                    <Username>{$username}</Username>
                                    <PasswordHash>{$passwordHash}</PasswordHash>
                                </MessageData>
                            </AIP>";
            fwrite($socket, chr(2) . $authRequest . chr(3));
            Log::info('Sent AuthenticationRequest', ['request' => $authRequest]);

            // Step 5: Read AuthenticationResponse
            $authResponse = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                Log::error('Stream timed out while waiting for AuthenticationResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }
            Log::info('Received AuthenticationResponse', ['authResponse' => $authResponse]);

            if (strpos($authResponse, "<Authenticated>1</Authenticated>") === false) {
                throw new \Exception("Authentication failed.");
            }
            Log::info('Authentication successful, session is active');


            // Step 7: Send the XML announcement message
            fwrite($socket, chr(2) . $xml . chr(3));
            Log::info('Sent AnnouncementTriggerRequest', ['xmlMessage' => $xml]);

            // Step 8: Read the final response
            $finalResponse = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                Log::error('Stream timed out while waiting for AnnouncementTriggerResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }
            fclose($socket);
            Log::info('Received response for AnnouncementTriggerRequest', ['response' => $finalResponse]);
        } catch (\Exception $e) {
            Log::error('Error during AviaVox communication', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $settings = AviavoxSetting::first();
        if (!$settings) {
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

            session()->flash('success', 'Announcement sent successfully');
            
            // Refresh the page immediately
            $this->js('window.location.reload()');

        } catch (\Exception $e) {
            Log::error('Failed to send announcement: ' . $e->getMessage());
            session()->flash('error', 'Failed to send announcement: ' . $e->getMessage());
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
