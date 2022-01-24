<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Flatpack;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request, $entity, $id = null)
    {
        $modelClass = $request->model;

        $composition = Flatpack::getTemplateComposition($entity, 'form.yaml');

        $entry = $modelClass::find($id);

        if (! $entry) {
            $entry = new $modelClass();
        }

        return view('flatpack::detail', [
            'model' => $modelClass,
            'entity' => $entity,
            'entry' => $entry,
            'composition' => $composition,
        ]);
    }
}
