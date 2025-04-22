<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AviavoxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AviavoxApiController extends Controller
{
    public function handleResponse(Request $request)
    {
        // Log the raw request data
        Log::info('Aviavox Raw Request Data', [
            'headers' => $request->headers->all(),
            'content' => $request->getContent(),
            'method' => $request->method(),
            'url' => $request->url(),
            'ip' => $request->ip()
        ]);

        // Enable user error handling for XML parsing
        libxml_use_internal_errors(true);
        
        // Parse the XML response
        $xml = simplexml_load_string($request->getContent());
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        if ($xml === false) {
            // Log XML parsing errors with more detail
            Log::error('Aviavox XML Parse Error', [
                'raw_response' => $request->getContent(),
                'errors' => array_map(function($error) {
                    return [
                        'level' => $error->level,
                        'code' => $error->code,
                        'message' => $error->message,
                        'line' => $error->line,
                        'column' => $error->column
                    ];
                }, $errors)
            ]);
            
            // Store the failed response
            AviavoxResponse::create([
                'status' => 'parse_error',
                'raw_response' => $request->getContent()
            ]);
            
            return response()->json(['status' => 'error', 'message' => 'Failed to parse XML']);
        }

        // Get the root element name
        $rootElement = $xml->getName();
        
        // Log the basic XML structure
        Log::info('Aviavox XML Structure', [
            'root_element' => $rootElement,
            'is_empty' => empty($xml->children()),
            'raw_xml' => $request->getContent()
        ]);

        // Check if this is an unnamed response
        if ($rootElement === 'unnamed') {
            Log::info('Aviavox Empty Unnamed Response', [
                'raw_response' => $request->getContent(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Store the unnamed response
            AviavoxResponse::create([
                'status' => 'unnamed',
                'raw_response' => $request->getContent()
            ]);
        }
        // Check if this is an announcement
        else if (isset($xml->Announcement)) {
            $announcement = $xml->Announcement;
            
            // Log the parsed announcement data
            Log::info('Aviavox Parsed Announcement', [
                'id' => (string)$announcement->ID,
                'status' => (string)$announcement->Status,
                'message_name' => (string)$announcement->MessageName,
                'content' => (string)$announcement->Text,
                'zones' => (string)$announcement->Zones,
                'description' => (string)$announcement->Description,
                'chain_id' => (string)$announcement->ChainID
            ]);
            
            // Store the response in the database
            AviavoxResponse::create([
                'announcement_id' => (string)$announcement->ID,
                'status' => (string)$announcement->Status,
                'message_name' => (string)$announcement->MessageName,
                'content' => (string)$announcement->Text,
                'zones' => (string)$announcement->Zones,
                'description' => (string)$announcement->Description,
                'raw_response' => $request->getContent()
            ]);
        } else {
            // Log the raw response if it's not an announcement or unnamed
            Log::info('Aviavox Unknown Response Type', [
                'raw_response' => $request->getContent(),
                'root_element' => $rootElement,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Store the raw response
            AviavoxResponse::create([
                'status' => 'unknown',
                'raw_response' => $request->getContent()
            ]);
        }
        
        return response()->json(['status' => 'success']);
    }
}