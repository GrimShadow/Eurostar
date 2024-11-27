<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AviavoxApiController extends Controller
{
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
}