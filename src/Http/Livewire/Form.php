<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithRelationships;
use Livewire\Component;

class Form extends Component
{
    use WithComposition, WithRelationships;

    /**
     * Form fields.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Form field components.
     *
     * @var array
     */
    public $formFields = [];

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
     * Model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Form type.
     *
     * @var string
     */
    public $formType;

    /**
     * Livewire component listeners.
     */
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

    // public function saveEditorState($editorJsonData)
    // {
    //     $this->fields['body'] = $editorJsonData;
    // }

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

        $this->entry->refresh();

        $this->bindModelToFields();

        $entity = strtolower(class_basename($this->model));

        $this->emit('notify', [
            "type" => "success",
            'message' => "The {$entity} has been saved.",
        ]);

        if ($redirect) {
            return $this->goBack();
        }
    }

    private function bindModelToFields()
    {
        $fields = $this->getCompositionFields();

        foreach ($fields as $key => $options) {
            if ($this->entry->$key instanceof \Illuminate\Support\Carbon) {
                $this->fields[$key] = $this->entry->$key->format('Y-m-d\TH:i:s');
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Collection) {
                $this->fields[$key] = $this->entry->$key->pluck('id')->toArray();
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Model) {
                $this->fields[$key] = $this->entry->$key->getKey();
            } else {
                $this->fields[$key] = $this->entry->$key;
            }
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

            if ($this->isRelationship($key)) {
                $this->syncRelationship($key);
            } else {
                $this->entry->{$key} = $this->fields[$key];
            }
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
