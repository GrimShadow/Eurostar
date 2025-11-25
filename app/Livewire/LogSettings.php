<?php

namespace App\Livewire;

use App\Models\LogSetting;
use App\Services\LogHelper;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

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
        // Get or create the log settings record (there should only be one)
        $defaults = [
            'gtfs_error_logs' => false,
            'gtfs_debug_logs' => false,
            'gtfs_information_logs' => false,
            'aviavox_error_logs' => false,
            'aviavox_debug_logs' => false,
            'aviavox_information_logs' => false,
            'automatic_rules_error_logs' => false,
            'automatic_rules_debug_logs' => false,
            'automatic_rules_information_logs' => false,
            'announcement_error_logs' => false,
            'announcement_debug_logs' => false,
            'announcement_information_logs' => false,
        ];

        $settings = LogSetting::firstOrCreate(['id' => 1], $defaults);

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

    public function updated($propertyName)
    {
        // This method is called whenever a property is updated via wire:model
        $validProperties = [
            'gtfs_error_logs', 'gtfs_debug_logs', 'gtfs_information_logs',
            'aviavox_error_logs', 'aviavox_debug_logs', 'aviavox_information_logs',
            'automatic_rules_error_logs', 'automatic_rules_debug_logs', 'automatic_rules_information_logs',
            'announcement_error_logs', 'announcement_debug_logs', 'announcement_information_logs',
        ];

        if (in_array($propertyName, $validProperties)) {
            try {
                // Get or create the log settings record
                $defaults = [
                    'gtfs_error_logs' => false,
                    'gtfs_debug_logs' => false,
                    'gtfs_information_logs' => false,
                    'aviavox_error_logs' => false,
                    'aviavox_debug_logs' => false,
                    'aviavox_information_logs' => false,
                    'automatic_rules_error_logs' => false,
                    'automatic_rules_debug_logs' => false,
                    'automatic_rules_information_logs' => false,
                    'announcement_error_logs' => false,
                    'announcement_debug_logs' => false,
                    'announcement_information_logs' => false,
                ];

                $settings = LogSetting::firstOrCreate(['id' => 1], $defaults);

                // Update the database record
                $settings->update([$propertyName => $this->$propertyName]);

                // Clear the LogHelper cache so new settings take effect immediately
                LogHelper::clearCache();

                // Log the change
                Log::info("Log setting updated: {$propertyName} = ".($this->$propertyName ? 'enabled' : 'disabled'));

                // Show success message
                session()->flash('message', 'Log setting updated successfully.');
            } catch (\Exception $e) {
                // Log the error for debugging
                Log::error('Failed to update log setting', [
                    'property' => $propertyName,
                    'value' => $this->$propertyName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Show error message to user
                session()->flash('error', 'Failed to update log setting. Please try again.');

                // Reload settings to revert the UI change
                $this->loadSettings();
            }
        }
    }

    public function render()
    {
        return view('livewire.log-settings');
    }
}
