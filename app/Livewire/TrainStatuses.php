<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Status;
use Livewire\WithPagination;

class TrainStatuses extends Component
{
    use WithPagination;

    public $newStatus = '';
    public $newColorName = '';
    public $newColorRgb = '';

    protected $rules = [
        'newStatus' => 'required|string|max:255|unique:statuses,status',
        'newColorName' => 'required|string|max:255',
        'newColorRgb' => 'required|regex:/^\d{1,3},\d{1,3},\d{1,3}$/'
    ];

    public function save()
    {
        $this->validate();

        Status::create([
            'status' => $this->newStatus,
            'color_name' => $this->newColorName,
            'color_rgb' => $this->newColorRgb
        ]);

        $this->reset(['newStatus', 'newColorName', 'newColorRgb']);
        session()->flash('success', 'Status created successfully.');
    }

    public function deleteStatus($id)
    {
        Status::find($id)->delete();
        session()->flash('success', 'Status deleted successfully.');
    }

    public function render()
    {
        return view('livewire.train-statuses', [
            'statuses' => Status::orderBy('status')->paginate(10)
        ]);
    }
}
