<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementsTable extends Component
{
    use WithPagination;

    protected $listeners = [
        'announcement-created' => '$refresh',
        'announcements-cleared' => '$refresh'
    ];

    public function deleteAnnouncement($id)
    {
        Announcement::find($id)->delete();
        $this->dispatch('announcement-deleted');
    }

    public function clearAllAnnouncements()
    {
        try {
            Announcement::truncate();
            $this->resetPage();
            $this->dispatch('announcements-cleared');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to clear announcements: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.announcements-table', [
            'announcements' => Announcement::orderBy('scheduled_time', 'desc')->paginate(10)
        ]);
    }
}
