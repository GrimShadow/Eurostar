<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VariablesController extends Controller
{
    public function index()
    {
        return view('variables');
    }
} 