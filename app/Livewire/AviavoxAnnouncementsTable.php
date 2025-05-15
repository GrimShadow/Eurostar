<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class AviavoxAnnouncementsTable extends Component
{
    use WithPagination;

    // Add unique pagination theme
    protected $paginationTheme = 'tailwind';

    // Add property to maintain scroll
    public bool $keepScrollOnPaginate = true;

    // Add pagination query string key
    protected function paginationQueryString(): array
    {
        return ['page' => ['as' => 'announcements-page']];
    }

    #[Url]
    public $page = 1;

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
            'announcements' => Announcement::orderBy('created_at', 'desc')
                ->paginate(10)
        ]);
    }
} 