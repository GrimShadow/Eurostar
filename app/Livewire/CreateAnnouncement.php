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
            $response = '';
            while (!feof($socket)) {
                $char = fread($socket, 1);
                if ($char === chr(3)) break; // End of message
                if ($char === chr(2)) continue; // Start of message
                $response .= $char;
            }
            
            if (stream_get_meta_data($socket)['timed_out']) {
                Log::error('Stream timed out while waiting for AuthenticationChallengeResponse');
                throw new \Exception('Stream timed out while waiting for response');
            }
            Log::info('Received AuthenticationChallengeResponse', ['response' => $response]);

            // Extract challenge and handle both XML and HTTP responses
            $challenge = $this->extractChallengeFromResponse($response);
            if (!$challenge) {
                throw new \Exception('Challenge extraction failed from response.');
            }
            Log::info('Challenge code extracted successfully', ['challenge' => $challenge]);

            // Authentication steps remain the same...
            $saltedPassword = $password . ($challenge ^ strlen($password)) . strrev($password);
            $passwordHash = strtoupper(hash('sha512', $saltedPassword));

            // Send announcement with proper headers
            $announcementRequest = chr(2) . $xml . chr(3);
            fwrite($socket, $announcementRequest);
            Log::info('Sent AnnouncementTriggerRequest', ['xmlMessage' => $xml]);

            // Read response with proper handling for both XML and HTTP
            $finalResponse = '';
            while (!feof($socket)) {
                $char = fread($socket, 1);
                if ($char === chr(3)) break; // End of message
                if ($char === chr(2)) continue; // Start of message
                $finalResponse .= $char;
            }

            // Parse the response
            if (strpos($finalResponse, '<?xml') !== false) {
                // Handle XML response
                $this->handleXmlResponse($finalResponse);
            } else if (strpos($finalResponse, 'HTTP/') !== false) {
                // Handle HTTP response
                $this->handleHttpResponse($finalResponse);
            }

            fclose($socket);
            Log::info('Communication completed successfully');

        } catch (\Exception $e) {
            Log::error('Error during AviaVox communication', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    private function handleXmlResponse($response)
    {
        Log::info('Received XML response', ['response' => $response]);
        
        // Parse XML response
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            throw new \Exception('Failed to parse XML response');
        }

        // Handle announcement status
        if (isset($xml->Announcement->Status)) {
            $status = (string)$xml->Announcement->Status;
            Log::info('Announcement status', ['status' => $status]);
            
            if ($status === 'Playing') {
                session()->flash('message', 'Announcement is now playing');
            }
        }
    }

    private function handleHttpResponse($response)
    {
        Log::info('Received HTTP response', ['response' => $response]);
        
        // Parse HTTP response
        $lines = explode("\n", $response);
        $statusLine = $lines[0];
        
        if (strpos($statusLine, '200 OK') !== false) {
            session()->flash('message', 'Announcement sent successfully');
        } else {
            throw new \Exception('Unexpected HTTP response: ' . $statusLine);
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
                $xml = "<AIP>
                            <MessageID>AnnouncementTriggerRequest</MessageID>
                            <MessageData>
                                <AnnouncementData>
                                    <Item ID=\"{$selected->item_id}\" Value=\"{$selected->value}\"/>
                                </AnnouncementData>
                            </MessageData>
                        </AIP>";

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
        // Try XML format first
        if (strpos($response, '<?xml') !== false) {
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            return $matches[1] ?? null;
        }
        
        // Try HTTP response format
        if (strpos($response, 'HTTP/') !== false) {
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            return $matches[1] ?? null;
        }
        
        return null;
    }
}
