<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AviavoxAnnouncement;
use App\Models\AviavoxSetting;
use App\Models\Zone;
use App\Models\GtfsTrip;
use App\Models\PendingAnnouncement;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateAnnouncement extends Component
{
    public $type = '';
    public $message = '';
    public $scheduled_time = '';
    public $recurrence = '';
    public $author = '';
    public $area = '';
    public $selectedAnnouncement = '';
    public $audioAnnouncements;
    public $zones;
    public $selectedTrain = '';
    public $trains = [];

    protected $rules = [
        'type' => 'required|in:audio,text',
        'message' => 'required_if:type,text',
        'scheduled_time' => 'required',
        'recurrence' => 'nullable',
        'author' => 'required',
        'area' => 'required',
        'selectedAnnouncement' => 'required_if:type,audio',
        'selectedTrain' => 'required_if:type,audio'
    ];

    public function mount()
    {
        $this->loadTrains();
        $this->audioAnnouncements = AviavoxAnnouncement::all();
        $this->zones = Zone::orderBy('value')->get();
    }

    private function loadTrains()
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $this->trains = GtfsTrip::query()
            ->join('gtfs_calendar_dates', 'gtfs_trips.service_id', '=', 'gtfs_calendar_dates.service_id')
            ->join('gtfs_stop_times', 'gtfs_trips.trip_id', '=', 'gtfs_stop_times.trip_id')
            ->where('gtfs_trips.route_id', 'like', 'NLAMA%')
            ->whereDate('gtfs_calendar_dates.date', $today)
            ->where('gtfs_calendar_dates.exception_type', 1)
            ->where('gtfs_stop_times.stop_sequence', 1)
            ->where('gtfs_stop_times.departure_time', '>=', $currentTime)
            ->select([
                'gtfs_trips.trip_headsign as number',
                'gtfs_stop_times.departure_time'
            ])
            ->orderBy('gtfs_stop_times.departure_time')
            ->limit(6)
            ->get()
            ->toArray();
    }

    public function updatedType()
    {
        $this->selectedAnnouncement = '';
        $this->message = '';
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
        $this->validate();

        if ($this->type === 'audio') {
            $settings = AviavoxSetting::first();
            if (!$settings) {
                session()->flash('error', 'Aviavox settings are not configured.');
                return;
            }

            // Find the selected train's departure time
            $selectedTrainData = collect($this->trains)->firstWhere('number', $this->selectedTrain);
            $trainDepartureTime = $selectedTrainData['departure_time'];
            
            // Format the date time using today's date and train's departure time
            $scheduledDateTime = Carbon::now()->format('Y-m-d') . 'T' . substr($trainDepartureTime, 0, 5) . ':00';

            $xml = "<AIP>
                <MessageID>AnnouncementTriggerRequest</MessageID>
                <MessageData>
                    <AnnouncementData>
                        <Item ID=\"MessageName\" Value=\"CHECKIN_WELCOME_CLOSED\"/>
                        <Item ID=\"TrainNumber\" Value=\"{$this->selectedTrain}\"/>
                        <Item ID=\"Route\" Value=\"GBR_LON\"/>
                        <Item ID=\"ScheduledTime\" Value=\"{$scheduledDateTime}\"/>
                        <Item ID=\"Zones\" Value=\"{$this->area}\"/>
                    </AnnouncementData>
                </MessageData>
            </AIP>";

            // Queue the announcement instead of sending directly
            PendingAnnouncement::create([
                'xml_content' => $xml,
                'status' => 'pending'
            ]);

            session()->flash('success', 'Announcement queued successfully.');
        }

        Log::info('Creating announcement', ['type' => $this->type, 'selectedAnnouncement' => $this->selectedAnnouncement]);

        $message = $this->type === 'text' ? $this->message : ($this->type === 'audio' ? $this->audioAnnouncements->find($this->selectedAnnouncement)?->name : null);
        $announcement = Announcement::create([
            'type' => $this->type,
            'message' => $message,
            'scheduled_time' => $this->scheduled_time,
            'recurrence' => $this->recurrence,
            'author' => $this->author,
            'area' => $this->area,
            'status' => 'Pending'
        ]);
        Log::info('Announcement created in database', ['id' => $announcement->id, 'type' => $announcement->type, 'message' => $announcement->message]);

        $this->reset(['type', 'message', 'scheduled_time', 'recurrence', 'author', 'area', 'selectedAnnouncement']);
        $this->dispatch('announcement-created');
        $this->dispatch('close-modal');
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
