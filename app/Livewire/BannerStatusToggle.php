<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class BannerStatusToggle extends Component
{
    public $bannerStatus;

    public function mount()
    {
        $this->bannerStatus = Setting::firstOrCreate(
            ['key' => 'banner_status'],
            ['value' => true]
        )->value;
    }

    public function toggleBanner()
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'banner_status'],
            ['value' => true]
        );
        
        $setting->value = !$this->bannerStatus;
        $setting->save();
        
        $this->bannerStatus = $setting->value;
    }

    public function render()
    {
        return view('livewire.banner-status-toggle');
    }
} 