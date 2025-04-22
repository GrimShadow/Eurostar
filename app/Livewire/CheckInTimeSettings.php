<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CheckInTimeSettings extends Component
{
    public $globalCheckInOffset = 90; // Default 90 minutes
    public $specificTrainTimes = [];
    public $newTrainId = '';
    public $newTrainOffset = 90;

    public function mount()
    {
        // Load global setting
        $this->globalCheckInOffset = Setting::where('key', 'global_check_in_offset')->value('value') ?? 90;
        
        // Load specific train settings
        $specificTimes = Setting::where('key', 'specific_train_check_in_times')->value('value');
        $this->specificTrainTimes = $specificTimes ? json_decode($specificTimes, true) : [];
    }

    public function updateGlobalOffset()
    {
        Setting::updateOrCreate(
            ['key' => 'global_check_in_offset'],
            ['value' => $this->globalCheckInOffset]
        );
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Global check-in time offset updated successfully.'
        ]);
    }

    public function addSpecificTrain()
    {
        $this->validate([
            'newTrainId' => 'required|string',
            'newTrainOffset' => 'required|integer|min:1'
        ]);

        $this->specificTrainTimes[$this->newTrainId] = $this->newTrainOffset;
        
        Setting::updateOrCreate(
            ['key' => 'specific_train_check_in_times'],
            ['value' => json_encode($this->specificTrainTimes)]
        );

        $this->newTrainId = '';
        $this->newTrainOffset = 90;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Specific train check-in time added successfully.'
        ]);
    }

    public function removeSpecificTrain($trainId)
    {
        unset($this->specificTrainTimes[$trainId]);
        
        Setting::updateOrCreate(
            ['key' => 'specific_train_check_in_times'],
            ['value' => json_encode($this->specificTrainTimes)]
        );
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Specific train check-in time removed successfully.'
        ]);
    }

    public function render()
    {
        return view('livewire.check-in-time-settings');
    }
} 