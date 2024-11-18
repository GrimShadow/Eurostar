<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementsTable extends Component
{
    use WithPagination;

    protected $listeners = ['announcement-created' => '$refresh'];

    public function deleteAnnouncement(Announcement $announcement)
    {
        $announcement->delete();
        session()->flash('success', 'Announcement deleted successfully.');
    }

    public function render()
    {
        return view('livewire.announcements-table', [
            'announcements' => Announcement::orderBy('scheduled_time', 'desc')->paginate(10)
        ]);
    }
}
