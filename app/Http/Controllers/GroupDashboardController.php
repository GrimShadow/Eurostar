<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupDashboardController extends Controller
{
    public function index(Group $group)
    {
        return view('group.dashboard', compact('group'));
    }
} 