<?php

namespace App\Livewire;

use App\Models\Reason;
use Livewire\Component;

class ReasonsManager extends Component
{
    public $reasons = [];
    public $code = '';
    public $name = '';
    public $description = '';
    public $editingId = null;

    public function mount()
    {
        $this->loadReasons();
    }

    public function loadReasons()
    {
        $this->reasons = Reason::orderBy('code')->get();
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:reasons,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->editingId) {
            $reason = Reason::find($this->editingId);
            $reason->update([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
            ]);
        } else {
            Reason::create([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
            ]);
        }

        $this->reset(['code', 'name', 'description', 'editingId']);
        $this->loadReasons();
    }

    public function edit($id)
    {
        $reason = Reason::find($id);
        $this->editingId = $id;
        $this->code = $reason->code;
        $this->name = $reason->name;
        $this->description = $reason->description;
    }

    public function delete($id)
    {
        Reason::find($id)->delete();
        $this->loadReasons();
    }

    public function cancel()
    {
        $this->reset(['code', 'name', 'description', 'editingId']);
    }

    public function render()
    {
        return view('livewire.reasons-manager');
    }
} 