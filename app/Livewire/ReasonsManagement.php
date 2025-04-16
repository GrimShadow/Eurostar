<?php

namespace App\Livewire;

use App\Models\Reason;
use Livewire\Component;
use Livewire\WithPagination;

class ReasonsManagement extends Component
{
    use WithPagination;

    public $code = '';
    public $name = '';
    public $description = '';
    public $editingId = null;
    public $showModal = false;

    protected $rules = [
        'code' => 'required|string|max:50|unique:reasons,code',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000'
    ];

    public function mount()
    {
        // No need to load reasons here as we'll use pagination in render
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function editReason($id)
    {
        $reason = Reason::find($id);
        $this->editingId = $id;
        $this->code = $reason->code;
        $this->name = $reason->name;
        $this->description = $reason->description;
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->editingId) {
            $this->rules['code'] = 'required|string|max:50|unique:reasons,code,' . $this->editingId;
        }

        $this->validate();

        if ($this->editingId) {
            $reason = Reason::find($this->editingId);
            $reason->update([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description
            ]);
            session()->flash('success', 'Reason updated successfully.');
        } else {
            Reason::create([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description
            ]);
            session()->flash('success', 'Reason created successfully.');
        }

        $this->closeModal();
    }

    public function deleteReason($id)
    {
        Reason::find($id)->delete();
        session()->flash('success', 'Reason deleted successfully.');
    }

    private function resetForm()
    {
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.reasons-management', [
            'reasons' => Reason::orderBy('name')->paginate(10)
        ]);
    }
}
