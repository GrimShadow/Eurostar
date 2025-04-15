<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class MaintenanceModeToggle extends Component
{
    public $maintenanceMode;

    public function mount()
    {
        $this->maintenanceMode = Setting::firstOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => false]
        )->value;
    }

    public function toggleMaintenanceMode()
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => false]
        );
        
        $setting->value = !$this->maintenanceMode;
        $setting->save();
        
        $this->maintenanceMode = $setting->value;
    }

    public function render()
    {
        return view('livewire.maintenance-mode-toggle');
    }
} 