<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;

class ClearAnnouncements extends Component
{
    public function clearAllAnnouncements()
    {
        try {
            Announcement::truncate();
            $this->dispatch('announcements-cleared');
            $this->dispatch('success', message: 'All announcements have been cleared successfully.');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to clear announcements: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.clear-announcements');
    }
} 