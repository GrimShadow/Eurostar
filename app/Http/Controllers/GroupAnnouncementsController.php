<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupAnnouncementsController extends Controller
{
    public function index(Group $group)
    {
        return view('group.announcements', compact('group'));
    }
} 