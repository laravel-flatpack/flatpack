<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Facades\Flatpack;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request, $entity, $id = null)
    {
        $modelClass = $request->flatpackMappings[$entity];

        $composition = Flatpack::loadComposition()->getTemplateComposition($entity, 'form.yaml');

        if ($id === 'create') {
            $entry = new $modelClass();
            $formType = 'create';
        } else {
            $entry = $modelClass::findOrFail($id);
            $formType = 'edit';
        }

        return view('flatpack::detail', [
            'model' => $modelClass,
            'entity' => $entity,
            'entry' => $entry,
            'composition' => $composition,
            'formType' => $formType,
        ]);
    }
}
