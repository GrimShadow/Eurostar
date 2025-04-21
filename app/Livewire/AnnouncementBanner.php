<?php

namespace App\Livewire;

use Livewire\Component;

class AnnouncementBanner extends Component
{
    public $showBanner = false;

    protected $listeners = ['showAnnouncementBanner'];

    public function showAnnouncementBanner()
    {
        $this->showBanner = true;
        $this->dispatch('announcement-banner-shown');
    }

    public function render()
    {
        return view('livewire.announcement-banner');
    }
} 