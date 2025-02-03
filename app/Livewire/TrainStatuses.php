<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Status;
use Livewire\WithPagination;

class TrainStatuses extends Component
{
    use WithPagination;

    public $newStatus = '';
    public $newColor = 'gray';

    protected $rules = [
        'newStatus' => 'required|string|max:255|unique:statuses,status',
        'newColor' => 'required|in:gray,red,green,yellow'
    ];

    public function save()
    {
        $this->validate();

        Status::create([
            'status' => $this->newStatus,
            'color' => $this->newColor
        ]);

        $this->reset(['newStatus', 'newColor']);
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
