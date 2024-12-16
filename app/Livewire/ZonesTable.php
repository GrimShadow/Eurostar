<?php

namespace App\Livewire;

use App\Models\Zone;
use Livewire\Component;
use Livewire\WithPagination;

class ZonesTable extends Component
{
    use WithPagination;

    public $value = '';

    protected $rules = [
        'value' => 'required|string|max:255'
    ];

    public function addZone()
    {
        $this->validate();

        Zone::create([
            'value' => $this->value
        ]);

        $this->value = '';
        session()->flash('success', 'Zone added successfully.');
    }

    public function deleteZone($id)
    {
        Zone::find($id)->delete();
        session()->flash('success', 'Zone deleted successfully.');
    }

    public function render()
    {
        return view('livewire.zones-table', [
            'zones' => Zone::orderBy('created_at', 'desc')->paginate(10)
        ]);
    }
}
