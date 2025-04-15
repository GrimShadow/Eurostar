<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ActiveUsers extends Component
{
    public $activeUsers;

    public function mount()
    {
        $this->loadActiveUsers();
    }

    public function loadActiveUsers()
    {
        $this->activeUsers = User::where(function($query) {
            $query->where('last_activity_at', '>=', now()->subMinutes(5))
                  ->orWhereNull('last_activity_at');
        })
        ->orderBy('last_activity_at', 'desc')
        ->get();
    }

    public function logoutUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            // Clear the user's session
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
            
            // Update last activity to force logout
            $user->last_activity_at = now()->subHours(2);
            $user->save();
            
            $this->loadActiveUsers();
            $this->dispatch('user-logged-out', message: "User {$user->name} has been logged out.");
        }
    }

    public function render()
    {
        return view('livewire.active-users');
    }
} 