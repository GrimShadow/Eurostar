<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

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
        $announcements = Announcement::whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.announcements-table', [
            'announcements' => $announcements
        ]);
    }
}
