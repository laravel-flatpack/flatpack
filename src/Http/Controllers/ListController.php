<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Flatpack;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index(Request $request, $entity)
    {
        $modelClass = $request->model;

        $composition = Flatpack::getTemplateComposition($entity, 'list.yaml');

        return view('flatpack::list', [
                'composition' => $composition,
                'entity' => $entity,
                'model' => $modelClass,
            ]);
    }
}
