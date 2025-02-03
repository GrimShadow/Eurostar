<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementApiController extends Controller
{
    public function index()
    {
        $announcements = Announcement::orderBy('scheduled_time', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }
} 