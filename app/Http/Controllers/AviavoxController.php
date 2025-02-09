<?php

namespace App\Http\Controllers;

use App\Models\AviavoxSetting;
use App\Models\AviavoxAnnouncement;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class AviavoxController extends Controller
{
    public function viewAviavox()
    {
        $aviavoxSettings = AviavoxSetting::first();
        $announcements = AviavoxAnnouncement::all();
        return view('settings.aviavox', compact('aviavoxSettings', 'announcements'));
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
}