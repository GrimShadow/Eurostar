<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class SelectorController extends Controller
{
    public function index(Request $request)
    {
        return view('selector', [
            'groups' => Group::whereHas('users', function ($query) use ($request) {
                    $query->where('users.id', $request->user()->id);
                })
                ->where('active', true)
                ->orderBy('name')
                ->get()
        ]);
    }
} 