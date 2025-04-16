<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements.
     */
    public function index()
    {
        $announcements = Announcement::orderBy('scheduled_time', 'desc')->get();
        
        return view('announcements', [
            'announcements' => $announcements
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:audio,text',
            'message' => 'required',
            'scheduled_time' => 'required',
            'recurrence' => 'nullable',
            'author' => 'required',
            'area' => 'required',
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'status' => 'Pending'
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully.');
    }
    

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'type' => 'required|in:audio,text',
            'message' => 'required',
            'scheduled_time' => 'required',
            'recurrence' => 'nullable',
            'author' => 'required',
            'area' => 'required',
            'status' => 'required|in:Pending,Finished,Cancelled'
        ]);

        $announcement->update($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    public function clear()
    {
        try {
            Announcement::truncate();
            return redirect()->route('announcements')
                ->with('success', 'All announcements have been cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->route('announcements')
                ->with('error', 'Failed to clear announcements: ' . $e->getMessage());
        }
    }
}
