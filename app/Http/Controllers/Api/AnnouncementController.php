<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    /**
     * Get the last 5 announcements
     */
    public function getLatestAnnouncements(): JsonResponse
    {
        $announcements = Announcement::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'type' => $announcement->type,
                    'message' => $announcement->message,
                    'scheduled_time' => $announcement->scheduled_time,
                    'author' => $announcement->author,
                    'area' => $announcement->area,
                    'status' => $announcement->status,
                    'created_at' => $announcement->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $announcements,
            'count' => $announcements->count(),
        ]);
    }
} 