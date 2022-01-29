<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\View\Components\FormField;
use Livewire\Component;

class Form extends Component
{
    use WithComposition;

    /**
     * Form fields.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Form relations.
     *
     * @var array
     */
    public $relations = [];

    /**
     * Form field components.
     *
     * @var array
     */
    public $formFields = [];

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

    private function getFieldComponent($field, $options)
    {
        return new FormField($field, $options, $this->fields[$field] ?? null);
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
        // $this->validate();

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
        $modelRelations = array_keys($this->entry->getRelations());
        foreach ($this->fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                in_array($key, $modelRelations) ||
                $this->fields[$key] === null) {
                continue;
            }
            $this->entry->{$key} = $this->fields[$key];
        }
    }

    protected function getCompositionFields()
    {
        return array_merge(
            $this->getComposition('header'),
            $this->getComposition('fields'),
            $this->getComposition('sidebar')
        );
    }

    private function bindModelToFields()
    {
        $fields = $this->getCompositionFields();

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
