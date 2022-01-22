<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return view('flatpack::home');
    }
}
