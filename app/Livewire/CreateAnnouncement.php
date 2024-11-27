<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AviavoxAnnouncement;
use App\Models\AviavoxSetting;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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

    protected $rules = [
        'type' => 'required|in:audio,text',
        'message' => 'required_if:type,text',
        'scheduled_time' => 'required',
        'recurrence' => 'nullable',
        'author' => 'required',
        'area' => 'required',
        'selectedAnnouncement' => 'required_if:type,audio',
    ];

    public function mount()
    {
        $this->audioAnnouncements = AviavoxAnnouncement::all();
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
            
            // Create SSL context
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            // Use tls:// for secure connection
            $socket = stream_socket_client(
                "tcp://{$server}:{$port}", 
                $errno, 
                $errstr, 
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
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

            // Send the announcement and wait for immediate response
            $formattedXml = str_replace(["\n", " "], '', $xml);
            fwrite($socket, chr(2) . $formattedXml . chr(3));
            Log::info('Sent AnnouncementTriggerRequest', ['xmlMessage' => $formattedXml]);

            // Read the immediate response
            $triggerResponse = fread($socket, 1024);
            Log::info('Received immediate response for AnnouncementTriggerRequest', ['response' => $triggerResponse]);

            if (strpos($triggerResponse, 'ErrorResponse') !== false) {
                throw new \Exception('Error in announcement trigger: ' . $triggerResponse);
            }

            // Close the socket after getting the immediate response
            fclose($socket);
            Log::info('Socket closed, waiting for status updates via HTTP');
            
            session()->flash('message', 'Announcement sent successfully. Waiting for confirmation...');
        } catch (\Exception $e) {
            Log::error('Error during AviaVox communication', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate();
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

        if ($this->type === 'audio' && $this->selectedAnnouncement) {
            Log::info('Preparing to process audio announcement', ['selectedAnnouncement' => $this->selectedAnnouncement]);
            $selected = AviavoxAnnouncement::find($this->selectedAnnouncement);
            if ($selected) {
                Log::info('Audio announcement details found', ['id' => $selected->id, 'name' => $selected->name, 'item_id' => $selected->item_id, 'value' => $selected->value]);

                $settings = AviavoxSetting::first();
                if (!$settings) {
                    Log::error('Aviavox settings not found in database');
                    session()->flash('error', 'Aviavox settings are not configured.');
                    return;
                }

                $server = $settings->ip_address;
                $port = $settings->port;

                // Prepare the XML message
                $xml = sprintf('<AIP><MessageID>AnnouncementTriggerRequest</MessageID><ClientID>1234567</ClientID><MessageData><AnnouncementData><Item ID="%s" Value="%s"/></AnnouncementData></MessageData></AIP>', 
                    $selected->item_id, 
                    $selected->value
                );

                // Authenticate and send the message in the same session
                $this->authenticateAndSendMessage($server, $port, $settings->username, $settings->password, $xml);
            } else {
                Log::error('Audio announcement not found', ['selected_id' => $this->selectedAnnouncement]);
            }
        }

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
