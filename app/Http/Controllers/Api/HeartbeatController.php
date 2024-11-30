<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsHeartbeat;
use App\Events\HeartbeatReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HeartbeatController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'timestamp' => 'required|date',
            'status' => 'required|string',
            'statusReason' => 'nullable|string',
            'lastUpdateSentTimestamp' => 'required|date'
        ]);

        try {

            $heartbeat = GtfsHeartbeat::create([
                'timestamp' => $request->timestamp,
                'status' => $request->status,
                'status_reason' => $request->statusReason,
                'last_update_sent_timestamp' => $request->lastUpdateSentTimestamp
            ]);

            event(new HeartbeatReceived($heartbeat));

            return response()->json([
                'status' => 'success',
                'message' => 'Heartbeat received'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process heartbeat'
            ], 500);
        }
    }
}