<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index(Request $request, $entity)
    {
        dump($entity, $request->model);

        return view('flatpack::list');
    }
}
