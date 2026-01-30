<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Models\Status;
use Livewire\Component;
use Livewire\WithPagination;

class TrainStatuses extends Component
{
    use WithPagination;

    public $newStatus = '';

    public $newColorName = '';

    public $newColorRgb = '';

    /** @var int|null Default status id for new trains (no StopStatus yet). */
    public $defaultTrainStatusId = null;

    protected $rules = [
        'newStatus' => 'required|string|max:255|unique:statuses,status',
        'newColorName' => 'required|string|max:255',
        'newColorRgb' => 'required|regex:/^\d{1,3},\d{1,3},\d{1,3}$/',
    ];

    public function save()
    {
        $this->validate();

        Status::create([
            'status' => $this->newStatus,
            'color_name' => $this->newColorName,
            'color_rgb' => $this->newColorRgb,
        ]);

        $this->reset(['newStatus', 'newColorName', 'newColorRgb']);
        session()->flash('success', 'Status created successfully.');
    }

    public function mount(): void
    {
        $value = Setting::where('key', 'default_train_status_id')->value('value');
        if ($value !== null && $value !== '') {
            $this->defaultTrainStatusId = is_array($value) ? ($value[0] ?? null) : $value;
        }
    }

    public function saveDefaultStatus(): void
    {
        $this->validate([
            'defaultTrainStatusId' => 'nullable|exists:statuses,id',
        ]);
        $value = $this->defaultTrainStatusId ?: null;
        Setting::updateOrCreate(
            ['key' => 'default_train_status_id'],
            ['value' => $value]
        );
        session()->flash('success', 'Default status for new trains updated.');
    }

    public function deleteStatus($id)
    {
        Status::find($id)->delete();
        session()->flash('success', 'Status deleted successfully.');
    }

    public function render()
    {
        return view('livewire.train-statuses', [
            'statuses' => Status::orderByRaw('LOWER(status) ASC')->paginate(10),
            'allStatuses' => Status::orderByRaw('LOWER(status) ASC')->get(),
        ]);
    }
}
