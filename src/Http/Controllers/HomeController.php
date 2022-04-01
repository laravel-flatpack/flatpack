<?php

namespace Flatpack\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Flatpack dashboard
        return view('flatpack::home');
    }
}
