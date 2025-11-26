<?php

namespace App\Livewire;

use App\Models\CheckInStatus;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInStatuses extends Component
{
    use WithPagination;

    public $newStatus = '';

    public $newColorName = '';

    public $newColorRgb = '';

    protected $rules = [
        'newStatus' => 'required|string|max:255|unique:check_in_statuses,status',
        'newColorName' => 'required|string|max:255',
        'newColorRgb' => 'required|regex:/^\d{1,3},\d{1,3},\d{1,3}$/',
    ];

    public function save(): void
    {
        $this->validate();

        CheckInStatus::create([
            'status' => $this->newStatus,
            'color_name' => $this->newColorName,
            'color_rgb' => $this->newColorRgb,
        ]);

        // Clear the cache so the new status appears immediately
        Cache::forget('all_check_in_statuses');

        $this->reset(['newStatus', 'newColorName', 'newColorRgb']);
        session()->flash('success', 'Check-in status created successfully.');
    }

    public function deleteStatus($id): void
    {
        CheckInStatus::find($id)->delete();

        // Clear the cache so the deletion is reflected immediately
        Cache::forget('all_check_in_statuses');

        session()->flash('success', 'Check-in status deleted successfully.');
    }

    public function render()
    {
        return view('livewire.check-in-statuses', [
            'statuses' => CheckInStatus::orderByRaw('LOWER(status) ASC')->paginate(10),
        ]);
    }
}
