<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Facades\Flatpack;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index(Request $request, $entity)
    {
        $modelClass = $request->flatpackMappings[$entity];

        $composition = Flatpack::loadComposition()->getTemplateComposition($entity, 'list.yaml');

        return view('flatpack::list', [
            'composition' => $composition,
            'entity' => $entity,
            'model' => $modelClass,
        ]);
    }
}
