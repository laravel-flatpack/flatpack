<?php

namespace Flatpack\Http\Controllers;

use Flatpack\Traits\WithActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use WithActions;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * Active record.
     *
     * @var Model
     */
    public $entry;

    public function store(Request $request, $entity, $id)
    {
        $this->model = $request->flatpack['model'];

        $this->entity = $entity;

        $this->entry = $this->model::find($id);

        if (! $this->entry) {
            return $this->jsonResponse([
                'error' => __('Create the :model entry first.', [
                    'model' => class_basename($this->model),
                ]),
            ]);
        }

        if (! $request->hasFile('upload')) {
            return $this->jsonResponse([
                'error' => 'No file to upload',
            ]);
        }

        $url = $this->getAction('upload')
            ->setEntry($this->entry)
            ->addFile($request->file('upload'))
            ->run();

        return $this->jsonResponse([
            'url' => $url,
        ]);
    }

    private function jsonResponse($data = [], $code = 200)
    {
        return response()->json($data, $code);
    }
}
