<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\User;
use App\Models\Zone;
use Livewire\Component;
use Livewire\WithFileUploads;

class GroupManagement extends Component
{
    use WithFileUploads;

    public $groups;

    public $name;

    public $description;

    public $selectedUsers = [];

    public $selectedZones = [];

    public $editingGroupId;

    public $isEditing = false;

    public $showModal = false;

    public $availableUsers;

    public $availableZones;

    public $image;

    public $active = true;

    protected function rules()
    {
        return [
            'name' => ['required', 'min:2', 'unique:groups,name,'.$this->editingGroupId],
            'description' => 'nullable|max:255',
            'selectedUsers' => 'array',
            'selectedZones' => 'array',
            'selectedZones.*' => 'exists:zones,id',
            'image' => 'nullable|image|max:2048',
            'active' => 'boolean',
        ];
    }

    public function mount()
    {
        $this->loadGroups();
        $this->loadUsers();
        $this->loadZones();
    }

    public function loadGroups()
    {
        $this->groups = Group::with(['users', 'zones'])->get();
    }

    public function loadUsers()
    {
        $this->availableUsers = User::orderBy('name')->get();
    }

    public function loadZones()
    {
        $this->availableZones = Zone::orderBy('value')->get();
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

    public function editGroup($groupId)
    {
        $this->isEditing = true;
        $this->editingGroupId = $groupId;
        $group = Group::with(['users', 'zones'])->find($groupId);

        $this->name = $group->name;
        $this->description = $group->description;
        $this->selectedUsers = $group->users->pluck('id')->toArray();
        $this->selectedZones = $group->zones->pluck('id')->toArray();
        $this->image = null;
        $this->active = $group->active;

        $this->showModal = true;
    }

    public function saveGroup()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('groups', 'public');
        }

        if ($this->isEditing) {
            $group = Group::find($this->editingGroupId);
            $group->update($data);
            $group->users()->sync($this->selectedUsers);
            $group->zones()->sync($this->selectedZones);
            session()->flash('success', 'Group updated successfully.');
        } else {
            $group = Group::create($data);
            $group->users()->attach($this->selectedUsers);
            $group->zones()->attach($this->selectedZones);
            session()->flash('success', 'Group created successfully.');
        }

        $this->closeModal();
        $this->loadGroups();
    }

    public function toggleActive($groupId)
    {
        $group = Group::find($groupId);
        $group->update(['active' => ! $group->active]);
        $this->loadGroups();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->selectedUsers = [];
        $this->selectedZones = [];
        $this->isEditing = false;
        $this->editingGroupId = null;
        $this->image = null;
        $this->active = true;
        $this->resetValidation();
    }

    public function deleteGroup($groupId)
    {
        Group::find($groupId)->delete();
        session()->flash('success', 'Group deleted successfully.');
        $this->loadGroups();
    }

    public function removeUser($userId)
    {
        $this->selectedUsers = array_values(array_diff($this->selectedUsers, [$userId]));
    }

    public function clearSelection()
    {
        $this->selectedUsers = [];
    }

    public function clearZoneSelection()
    {
        $this->selectedZones = [];
    }

    public function render()
    {
        return view('livewire.group-management');
    }
}
