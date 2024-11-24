<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserManagement extends Component
{
    public $users;
    public $name;
    public $email;
    public $password;
    public $showAddModal = false;

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8'
    ];

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = User::all();
    }

    public function openAddUserModal()
    {
        $this->resetForm();
        $this->showAddModal = true;
    }

    public function closeAddUserModal()
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    public function saveUser()
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $this->closeAddUserModal();
        $this->loadUsers();
        session()->flash('success', 'User created successfully.');
    }

    private function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.user-management');
    }
}