<?php

namespace App\Livewire;

use App\Models\LogSetting;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class LogSettings extends Component
{
    // GTFS Logging Flags
    public $gtfs_error_logs = false;
    public $gtfs_debug_logs = false;
    public $gtfs_information_logs = false;
    
    // Aviavox Logging Flags
    public $aviavox_error_logs = false;
    public $aviavox_debug_logs = false;
    public $aviavox_information_logs = false;
    
    // Automatic Rules Logging Flags
    public $automatic_rules_error_logs = false;
    public $automatic_rules_debug_logs = false;
    public $automatic_rules_information_logs = false;
    
    // Announcement Logging Flags
    public $announcement_error_logs = false;
    public $announcement_debug_logs = false;
    public $announcement_information_logs = false;

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        // Get the log settings record (there should only be one)
        $settings = LogSetting::first();
        
        if ($settings) {
            $this->gtfs_error_logs = $settings->gtfs_error_logs;
            $this->gtfs_debug_logs = $settings->gtfs_debug_logs;
            $this->gtfs_information_logs = $settings->gtfs_information_logs;
            
            $this->aviavox_error_logs = $settings->aviavox_error_logs;
            $this->aviavox_debug_logs = $settings->aviavox_debug_logs;
            $this->aviavox_information_logs = $settings->aviavox_information_logs;
            
            $this->automatic_rules_error_logs = $settings->automatic_rules_error_logs;
            $this->automatic_rules_debug_logs = $settings->automatic_rules_debug_logs;
            $this->automatic_rules_information_logs = $settings->automatic_rules_information_logs;
            
            $this->announcement_error_logs = $settings->announcement_error_logs;
            $this->announcement_debug_logs = $settings->announcement_debug_logs;
            $this->announcement_information_logs = $settings->announcement_information_logs;
        }
    }

    public function updated($propertyName)
    {
        // This method is called whenever a property is updated via wire:model
        $validProperties = [
            'gtfs_error_logs', 'gtfs_debug_logs', 'gtfs_information_logs',
            'aviavox_error_logs', 'aviavox_debug_logs', 'aviavox_information_logs',
            'automatic_rules_error_logs', 'automatic_rules_debug_logs', 'automatic_rules_information_logs',
            'announcement_error_logs', 'announcement_debug_logs', 'announcement_information_logs'
        ];

        if (in_array($propertyName, $validProperties)) {
            // Update the database record
            $settings = LogSetting::first();
            if ($settings) {
                $settings->update([$propertyName => $this->$propertyName]);
                
                // Log the change
                Log::info("Log setting updated: {$propertyName} = " . ($this->$propertyName ? 'enabled' : 'disabled'));
                
                // Show success message
                session()->flash('message', 'Log setting updated successfully.');
            }
        }
    }

    public function render()
    {
        return view('livewire.log-settings');
    }
}
