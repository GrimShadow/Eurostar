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
use Illuminate\Support\Facades\Auth;

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
        'area' => 'required',
        'selectedAnnouncement' => 'required_if:type,audio',
        'selectedTrain' => 'required_if:type,audio'
    ];

    public function mount()
    {
        $this->loadTrains();
        $this->audioAnnouncements = AviavoxAnnouncement::all();
        $this->zones = Zone::orderBy('value')->get();
        $this->author = Auth::user()->name;
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

            try {
                $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 5);
                if (!$socket) {
                    throw new \Exception("Failed to connect: $errstr ($errno)");
                }

                // Set stream timeout
                stream_set_timeout($socket, 20);

                // Authentication steps
                $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
                fwrite($socket, chr(2) . $challengeRequest . chr(3));
                
                $response = fread($socket, 1024);
                preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
                $challenge = $matches[1] ?? null;
                
                if (!$challenge) {
                    throw new \Exception('Invalid challenge received.');
                }

                // Hash password
                $saltedPassword = $settings->password . ($challenge ^ strlen($settings->password)) . strrev($settings->password);
                $hash = strtoupper(hash('sha512', $saltedPassword));

                // Send authentication request
                $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
                fwrite($socket, chr(2) . $authRequest . chr(3));

                $authResponse = fread($socket, 1024);
                if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                    throw new \Exception('Authentication failed.');
                }

                // Get the announcement message name
                $announcement = AviavoxAnnouncement::find($this->selectedAnnouncement);
                
                // Create and send the announcement XML
                $xml = "<AIP>
                    <MessageID>AnnouncementTriggerRequest</MessageID>
                    <MessageData>
                        <AnnouncementData>
                            <Item ID=\"MessageName\" Value=\"{$announcement->name}\"/>
                            <Item ID=\"Zones\" Value=\"{$this->area}\"/>
                        </AnnouncementData>
                    </MessageData>
                </AIP>";

                // Send the announcement
                fwrite($socket, chr(2) . $xml . chr(3));
                $finalResponse = fread($socket, 1024);
                fclose($socket);

                // Create the announcement record
                $announcementRecord = Announcement::create([
                    'type' => $this->type,
                    'message' => AviavoxAnnouncement::find($this->selectedAnnouncement)->name,
                    'area' => $this->area,
                    'user_id' => Auth::id(),
                    'author' => Auth::user()->name,
                    'status' => 'sent'
                ]);

                Log::info('Announcement created in database', [
                    'id' => $announcementRecord->id,
                    'type' => $announcementRecord->type,
                    'message' => $announcementRecord->message
                ]);

                $this->dispatch('close-modal');
                $this->dispatch('announcement-created');
                session()->flash('success', 'Announcement sent and stored successfully.');
                
                return redirect()->route('announcements.index');
            } catch (\Exception $e) {
                Log::error('Failed to process announcement: ' . $e->getMessage());
                session()->flash('error', 'Failed to process announcement: ' . $e->getMessage());
                return;
            }
        }

        // Handle other announcement types if needed
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
