<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class MaintenanceModal extends Component
{
    public $showModal = false;

    public function mount()
    {
        $this->showModal = Setting::where('key', 'maintenance_mode')->value('value') ?? false;
    }

    public function render()
    {
        return view('livewire.maintenance-modal');
    }
} 