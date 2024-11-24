<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Illuminate\Validation\Rule;

class UserManagement extends Component
{
    public $users;
    public $name;
    public $email;
    public $password;
    public $role = 'user';
    public $editingUserId;
    public $isEditing = false;
    public $showModal = false;

    protected function rules()
    {
        return [
            'name' => 'required|min:2',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingUserId)],
            'password' => $this->isEditing ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:user,administrator'
        ];
    }

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = User::all();
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

    public function editUser($userId)
    {
        $this->isEditing = true;
        $this->editingUserId = $userId;
        $user = User::find($userId);
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        
        $this->showModal = true;
    }

    public function saveUser()
    {
        $this->validate();

        if ($this->isEditing) {
            $this->updateUser();
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
            ]);
            session()->flash('success', 'User created successfully.');
        }

        $this->closeModal();
        $this->loadUsers();
    }

    public function updateUser()
    {
        $user = User::find($this->editingUserId);
        
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        $user->update($userData);
        session()->flash('success', 'User updated successfully.');
    }

    private function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
        $this->isEditing = false;
        $this->editingUserId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.user-management');
    }
}