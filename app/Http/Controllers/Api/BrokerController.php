<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingAnnouncement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BrokerController extends Controller
{
    public function getPendingAnnouncements()
    {
        $announcements = PendingAnnouncement::where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        // Mark fetched announcements as processing
        foreach ($announcements as $announcement) {
            $announcement->update(['status' => 'processing']);
        }

        return response()->json(['announcements' => $announcements]);
    }

    public function updateAnnouncementStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,failed',
            'response' => 'required|string'
        ]);

        $announcement = PendingAnnouncement::findOrFail($id);
        $announcement->update([
            'status' => $request->status,
            'response' => $request->response,
            'processed_at' => Carbon::now()
        ]);

        return response()->json(['message' => 'Status updated successfully']);
    }
} 