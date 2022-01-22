<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request, $entity, $id = null)
    {
        dump($entity, $id);

        return view('flatpack::home');
    }
}
