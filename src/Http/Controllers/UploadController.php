<?php

namespace Faustoq\Flatpack\Http\Controllers;

use Faustoq\Flatpack\Traits\WithActions;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use WithActions;

    public function store(Request $request, $entity, $id)
    {
        $this->model = $request->flatpack['model'];

        $this->entity = $entity;

        $this->entry = $this->model::find($id);

        if (! $request->hasFile('upload')) {
            return $this->jsonResponse([ 'error' => 'No file to upload' ]);
        }

        try {
            $action = $this->getAction('upload')
                ->setEntry($this->entry)
                ->addFile($request->file('upload'));

            $url = $action->run();
        } catch (\Exception $e) {
            return $this->jsonResponse([ 'error' => $e->getMessage() ]);
        }

        return $this->jsonResponse([
            'url' => $url,
        ]);
    }

    private function jsonResponse($data = [])
    {
        return response()->json($data);
    }
}
