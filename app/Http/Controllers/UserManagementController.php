<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserManagementController extends Controller
{
    public function viewUsers()
    {
        $users = User::all();
        return view('settings.users', compact('users'));
    }
}