<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GtfsUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GtfsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'header' => 'required|array',
            'header.gtfs_realtime_version' => 'required|string',
            'header.incrementality' => 'required|integer',
            'header.timestamp' => 'required|integer',
            'entity' => 'required|array',
            'entity.*.id' => 'required|string',
            'entity.*.trip_update' => 'required|array',
            'entity.*.trip_update.stop_time_update' => 'required|array',
        ]);

        $gtfsUpdate = GtfsUpdate::create([
            'gtfs_realtime_version' => $validated['header']['gtfs_realtime_version'],
            'incrementality' => $validated['header']['incrementality'],
            'timestamp' => Carbon::createFromTimestamp($validated['header']['timestamp']),
            'entity_data' => $validated['entity']
        ]);

        return response()->json([
            'message' => 'GTFS update stored successfully',
            'data' => $gtfsUpdate
        ], 201);
    }

    public function index()
    {
        $updates = GtfsUpdate::latest('timestamp')->paginate(15);
        return response()->json($updates);
    }

    public function show(GtfsUpdate $gtfsUpdate)
    {
        return response()->json($gtfsUpdate);
    }
}