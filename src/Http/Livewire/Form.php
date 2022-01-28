<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Livewire\Component;

class Form extends Component
{
    use WithComposition;

    public $fields = [];

    /** Component props. */
    public $model;
    public $entity;
    public $entry;
    public $formType;

    protected $listeners = ['editorjs-save:flatpack-editor' => 'saveEditorState'];

    public function mount()
    {
        $this->bindModelToFields();
    }

    public function render()
    {
        return view('flatpack::components.form', [
            'header' => $this->getComposition('header'),
            'toolbar' => $this->getComposition('toolbar'),
            'form' => $this->getComposition('fields'),
            'sidebar' => $this->getComposition('sidebar'),
        ]);
    }

    public function saveEditorState($editorJsonData)
    {
        $this->fields['body'] = $editorJsonData;
    }

    public function action($method, $redirect = false)
    {
        // Cancel action
        if ($method === 'cancel') {
            return redirect()->route('flatpack.list', [
                'entity' => $this->entity,
            ]);
        }

        // Validate form fields
        // $this->entry->flatpackValidate();

        $this->bindFieldsToModel();

        if (! method_exists($this->model, $method)) {
            throw new \Exception("Action not found: $method");
        }

        // Calling the model's method

        $this->entry->{$method}();

        // Redirect to edit form after create
        if ($this->formType === 'create') {
            return redirect()->route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $this->entry->id,
            ]);
        }

        $this->bindModelToFields();

        $entity = class_basename($this->model);

        $this->emit('notify', [
            "type" => "success",
            'message' => "The {$entity} has been updated.",
        ]);

        if ($redirect) {
            return $this->goBack();
        }
    }

    private function bindFieldsToModel()
    {
        foreach ($this->fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                $this->fields[$key] === null) {
                continue;
            }
            $this->entry->{$key} = $this->fields[$key];
        }
    }

    private function bindModelToFields()
    {
        $fields = array_merge(
            $this->getComposition('header'),
            $this->getComposition('fields'),
            $this->getComposition('sidebar')
        );

        foreach ($fields as $key => $options) {
            if ($this->entry->$key instanceof \Illuminate\Support\Carbon) {
                $this->fields[$key] = $this->entry->$key->format('Y-m-d\TH:i:s');
            } else {
                $this->fields[$key] = optional($this->entry)->{$key};
            }
        }
    }

    private function goToEditForm()
    {
        $this->emit('redirect', [
            'url' => route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $this->entry->id,
            ]),
        ]);
    }

    private function goBack()
    {
        $this->emit('redirect', [
            'url' => route('flatpack.index', $this->entity),
        ]);
    }
}
