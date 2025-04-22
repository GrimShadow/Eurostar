<?php

namespace App\Http\Controllers;

use App\Models\AviavoxSetting;
use App\Models\AviavoxAnnouncement;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Models\AviavoxTemplate;
use App\Models\AviavoxResponse;

class AviavoxController extends Controller
{
    public function viewAviavox()
    {
        $aviavoxSettings = AviavoxSetting::first();
        $announcements = AviavoxAnnouncement::all();
        $templates = AviavoxTemplate::orderBy('created_at', 'desc')->get();
        $responses = AviavoxResponse::orderBy('created_at', 'desc')->paginate(10);
        
        // Load predefined messages from the text file
        $messagesFile = storage_path('app/aviavox/Eurostar - AviaVox AIP Message Triggers.txt');
        $predefinedMessages = [];
        $availableMessageNames = [];
        
        if (File::exists($messagesFile)) {
            $content = File::get($messagesFile);
            preg_match_all('/<Item ID="MessageName" Value="([^"]+)"/', $content, $matches);
            $availableMessageNames = array_unique($matches[1]);
            
            foreach ($availableMessageNames as $name) {
                preg_match_all('/<Item ID="([^"]+)" Value="[^"]+"\/>/', $content, $paramMatches);
                $parameters = array_diff($paramMatches[1], ['MessageName', 'Zones']);
                $predefinedMessages[$name] = array_values(array_unique($parameters));
            }
        }
        
        return view('settings.aviavox', compact('aviavoxSettings', 'announcements', 'predefinedMessages', 'availableMessageNames', 'templates', 'responses'));
    }

    public function updateAviavox(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'port' => 'required|numeric',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        AviavoxSetting::updateOrCreate(
            ['id' => 1],
            $validated
        );

        return redirect()->route('settings.aviavox')->with('success', 'Aviavox settings updated successfully.');
    }

    public function testConnection()
    {
        $settings = AviavoxSetting::first();

        try {
            // Create a TCP/IP socket
            $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 5);

            if (!$socket) {
                Log::error("Connection failed: $errstr ($errno)");
                return redirect()->route('settings.aviavox')->with('error', "Connection failed: $errstr ($errno)");
            }

            // Step 1: Send AuthenticationChallengeRequest
            $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            fwrite($socket, chr(2) . $challengeRequest . chr(3)); // Wrap in STX and ETX

            // Step 2: Read AuthenticationChallengeResponse
            $response = fread($socket, 1024);
            Log::info("Authentication Challenge Response: " . $response);

            if (strpos($response, '<MessageID>AuthenticationChallengeResponse</MessageID>') === false) {
                Log::error('Authentication challenge failed.');
                return redirect()->route('settings.aviavox')->with('error', 'Authentication challenge failed.');
            }

            // Extract challenge from response
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;
            if (!$challenge) {
                Log::error('Invalid challenge received.');
                return redirect()->route('settings.aviavox')->with('error', 'Invalid challenge received.');
            }

            // Step 3: Salt and hash the password
            $password = $settings->password;
            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password . $salt . strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));

            // Step 4: Send AuthenticationRequest
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            fwrite($socket, chr(2) . $authRequest . chr(3)); // Wrap in STX and ETX

            // Step 5: Read AuthenticationResponse
            $authResponse = fread($socket, 1024);
            Log::info("Authentication Response: " . $authResponse);

            fclose($socket);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') !== false) {
                Log::info('Connection tested successfully.');
                return redirect()->route('settings.aviavox')->with('success', 'Connection tested successfully.');
            } else {
                Log::error('Authentication failed.');
                return redirect()->route('settings.aviavox')->with('error', 'Authentication failed.');
            }
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return redirect()->route('settings.aviavox')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'item_id' => 'required|string',
            'value' => 'required|string',
        ]);

        AviavoxAnnouncement::create([
            'name' => $request->name,
            'item_id' => $request->item_id,
            'value' => $request->value,
        ]);

        return redirect()->route('settings.aviavox')->with('success', 'Announcement added successfully.');
    }

    public function viewLogs()
    {
        $logFilePath = storage_path('logs/laravel.log');

        if (File::exists($logFilePath)) {
            $logContents = File::get($logFilePath);
        } else {
            $logContents = 'No log file found.';
        }

        return view('view-logs', ['logContents' => $logContents]);
    }

    public function exportLogs()
    {
        $logFilePath = storage_path('logs/laravel.log');

        if (!File::exists($logFilePath)) {
            return redirect()->route('settings.logs')->with('error', 'Log file does not exist.');
        }

        // Read the contents of the log file
        $logContents = File::get($logFilePath);

        // Create a downloadable response with the new filename
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="TIAS.log"',
        ];

        return Response::make($logContents, 200, $headers);
    }

    public function handleResponse(Request $request)
    {
        Log::info('Received Aviavox response', ['response' => $request->getContent()]);
        
        // Parse the XML response
        $xml = simplexml_load_string($request->getContent());
        if ($xml) {
            // Store or process the announcement status
            if (isset($xml->Announcement)) {
                $announcement = $xml->Announcement;
                Log::info('Announcement status update', [
                    'id' => (string)$announcement->ID,
                    'status' => (string)$announcement->Status,
                    'message_name' => (string)$announcement->MessageName
                ]);
            }
        }
        
        return response()->json(['status' => 'success']);
    }

    public function deleteAnnouncement(AviavoxAnnouncement $announcement)
    {
        $announcement->delete();
        
        return redirect()->back()->with('success', 'Announcement deleted successfully');
    }

    public function sendCheckinAwareFault()
    {
        $settings = AviavoxSetting::first();

        try {
            $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 5);
            if (!$socket) {
                throw new \Exception("Failed to connect: $errstr ($errno)");
            }

            // Authentication steps (reusing from testConnection method)
            $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            fwrite($socket, chr(2) . $challengeRequest . chr(3));
            
            $response = fread($socket, 1024);
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;
            
            if (!$challenge) {
                throw new \Exception('Invalid challenge received.');
            }

            // Hash password
            $password = $settings->password;
            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password . $salt . strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));

            // Send authentication request
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            fwrite($socket, chr(2) . $authRequest . chr(3));

            $authResponse = fread($socket, 1024);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                throw new \Exception('Authentication failed.');
            }

            // Send the specific announcement message
            $announcementMessage = "<AIP>
                <MessageID>AnnouncementTriggerRequest</MessageID>
                <MessageData>
                    <AnnouncementData>
                        <Item ID=\"MessageName\" Value=\"CHECKIN_AWARE_FAULT\"/>
                        <Item ID=\"Zones\" Value=\"Terminal\"/>
                    </AnnouncementData>
                </MessageData>
            </AIP>";

            fwrite($socket, chr(2) . $announcementMessage . chr(3));
            $finalResponse = fread($socket, 1024);
            fclose($socket);

            return redirect()->back()->with('success', 'Check-in aware fault announcement sent successfully');
        } catch (Exception $e) {
            Log::error('Failed to send check-in aware fault announcement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    public function storeMessage(Request $request)
    {
        try {
            $request->validate([
                'message_name' => 'required|string',
                'zones' => 'required|string',
                'parameters' => 'array|nullable'
            ]);

            // Get Aviavox settings
            $settings = AviavoxSetting::first();
            if (!$settings) {
                throw new Exception('Aviavox settings not configured');
            }

            // Connect to Aviavox server
            $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Failed to connect: $errstr ($errno)");
            }

            // Build the XML message
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xml .= "<AIP>\n";
            $xml .= "\t<MessageID>AnnouncementTriggerRequest</MessageID>\n";
            $xml .= "\t<MessageData>\n";
            $xml .= "\t\t<AnnouncementData>\n";
            $xml .= "\t\t\t<Item ID=\"MessageName\" Value=\"{$request->message_name}\"/>\n";

            // Add any parameters if they exist
            if ($request->parameters) {
                foreach ($request->parameters as $key => $value) {
                    if (!empty($value)) {
                        // Format datetime parameters
                        if (in_array($key, ['ScheduledTime', 'PublicTime']) && $value) {
                            $datetime = new \DateTime($value);
                            $value = $datetime->format('Y-m-d\TH:i:s\Z');
                        }
                        $xml .= "\t\t\t<Item ID=\"{$key}\" Value=\"{$value}\"/>\n";
                    }
                }
            }

            $xml .= "\t\t\t<Item ID=\"Zones\" Value=\"{$request->zones}\"/>\n";
            $xml .= "\t\t</AnnouncementData>\n";
            $xml .= "\t</MessageData>\n";
            $xml .= "</AIP>";

            // Send the announcement
            fwrite($socket, chr(2) . $xml . chr(3));
            $response = fread($socket, 1024);
            fclose($socket);

            // Store the announcement in the database
            AviavoxAnnouncement::create([
                'name' => $request->message_name,
                'xml_content' => $xml,
                'type' => 'audio',
                'user_id' => auth()->id(),
                'item_id' => 'MessageName',
                'value' => $request->message_name
            ]);

            return redirect()->back()->with('success', 'Announcement sent and stored successfully');
        } catch (Exception $e) {
            Log::error('Failed to send announcement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    public function sendCustomAnnouncement(Request $request)
    {
        $request->validate([
            'custom_xml' => 'required|string'
        ]);

        $settings = AviavoxSetting::first();

        try {
            // Log the incoming XML for debugging
            Log::info('Attempting to send custom XML', ['xml' => $request->custom_xml]);
            
            $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 5);
            if (!$socket) {
                throw new \Exception("Failed to connect: $errstr ($errno)");
            }

            // Set stream timeout
            stream_set_timeout($socket, 20);

            // Authentication steps
            $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            fwrite($socket, chr(2) . $challengeRequest . chr(3));
            Log::info('Sent challenge request');
            
            $response = fread($socket, 1024);
            if (stream_get_meta_data($socket)['timed_out']) {
                throw new \Exception('Stream timed out while waiting for challenge response');
            }
            Log::info('Received challenge response', ['response' => $response]);

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
            Log::info('Sent auth request');

            $authResponse = fread($socket, 1024);
            Log::info('Received auth response', ['response' => $authResponse]);
            
            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                throw new \Exception('Authentication failed.');
            }

            // Clean up the XML - remove any extra whitespace and ensure proper formatting
            $cleanXml = preg_replace('/>\s+</', '><', trim($request->custom_xml));
            Log::info('Sending cleaned XML', ['xml' => $cleanXml]);

            // Send the custom announcement with STX/ETX characters
            fwrite($socket, chr(2) . $cleanXml . chr(3));
            
            // Wait for response
            $finalResponse = fread($socket, 1024);
            Log::info('Received final response', ['response' => $finalResponse]);
            
            fclose($socket);

            return redirect()->back()->with('success', 'Custom announcement sent successfully');
        } catch (Exception $e) {
            Log::error('Failed to send custom announcement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send announcement: ' . $e->getMessage());
        }
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:aviavox_templates,name',
            'friendly_name' => 'nullable|string',
            'xml_template' => 'required|string',
            'variables' => 'required|array'
        ]);

        try {
            // Parse XML to validate format
            $xml = simplexml_load_string($request->xml_template);
            
            AviavoxTemplate::create([
                'name' => $request->name,
                'friendly_name' => $request->friendly_name,
                'xml_template' => $request->xml_template,
                'variables' => $request->variables
            ]);

            return redirect()->back()->with('success', 'Announcement template added successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid XML format: ' . $e->getMessage());
        }
    }

    public function deleteTemplate(AviavoxTemplate $template)
    {
        $template->delete();
        return redirect()->back()->with('success', 'Template deleted successfully');
    }
}