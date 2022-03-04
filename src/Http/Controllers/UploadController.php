<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Traits\WithActions;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use WithActions;

    public function store(Request $request, $entity, $id)
    {
        $this->model = $request->flatpackMappings[$entity];

        $this->entity = $entity;

        $this->entry = $this->model::find($id);

        if (! $request->hasFile('upload')) {
            return response()->json([
                'error' => 'No file to upload',
            ]);
        }

        // Get action instance
        $action = $this->getAction('upload')
            ->setEntry($this->entry)
            ->withFile($request->file('upload'));

        // Execute action
        $url = $action->run();

        return response()->json([
            'url' => $url,
        ]);
    }
}
