<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Support\Arr;
use Livewire\Component;

class Form extends Component
{
    use WithComposition;
    use WithRelationships;

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
    protected $listeners = [
        // 'editorjs-save:flatpack-editor' => 'saveEditorState',
        'flatpack-taginput:change' => 'saveTagInputState',
        'flatpack-taginput:new-tag' => 'createRelatedTag',
    ];

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

    public function saveTagInputState($key, $data)
    {
        $this->fields[$key] = explode(',', $data);
    }

    public function createRelatedTag($key, $data)
    {
        try {
            $fields = $this->getFormFields();
            $nameField = Arr::get($fields, "$key.relation.display", "title");

            $created = $this->createRelationship(
                $key,
                $nameField,
                $data
            );

            $this->emit("flatpack-taginput:new-tag-created:{$key}", $created);
            //
        } catch (\Exception $e) {
            $this->emit('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // public function saveEditorState($data)
    // {
    //     $this->fields['body'] = $data;
    // }

    public function save()
    {
        // $this->validate();

        // Assign model properties
        $this->bindFieldsToModel();

        $this->entry->save();

        // Save relationships
        $this->syncFieldsToRelations();

        // Redirect to edit form after create
        if ($this->formType === 'create') {
            return redirect()->route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $this->entry->id,
            ]);
        }

        // Bind refreshed model to fields
        $this->bindModelToFields();

        $this->emit('notify', [
            "type" => "success",
            'message' => class_basename($this->entry) . " saved.",
        ]);
    }

    public function action($method, $redirect = false)
    {
        // Cancel action
        if ($method === 'cancel') {
            return redirect()->route('flatpack.list', [
                'entity' => $this->entity,
            ]);
        }

        if ($method === 'save') {
            return $this->save();
        }

        if (! method_exists($this->model, $method)) {
            throw new \Exception("Action not found: $method");
        }

        // $this->validate();

        // Assign model properties
        // $this->bindFieldsToModel();

        // // Calling the model's method
        // $this->entry->{$method}();

        // // Save relationships
        // if ($method === 'save') {
        //     $this->syncFieldsToRelations();

        //     // Redirect to edit form after create
        //     if ($this->formType === 'create') {
        //         return redirect()->route('flatpack.form', [
        //             'entity' => $this->entity,
        //             'id' => $this->entry->id,
        //         ]);
        //     }
        // }

        // // Bind refreshed model to fields
        // $this->bindModelToFields();

        // $this->emit('notify', [
        //     "type" => "success",
        //     'message' => class_basename($this->entry) . " updated.",
        // ]);

        // if ($redirect) {
        //     return $this->goBack();
        // }
    }

    private function bindModelToFields()
    {
        $fields = $this->getFormFields();

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
        $fields = $this->getFormFields();

        foreach ($fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                $this->isRelationship($key) ||
                $this->fields[$key] === null) {
                continue;
            }

            $this->entry->{$key} = $this->fields[$key];
        }
    }

    private function syncFieldsToRelations()
    {
        $fields = $this->getFormFields();

        foreach ($fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                !$this->isRelationship($key)) {
                continue;
            }

            $this->syncRelationship($key);
        }
        $this->entry->save();
        $this->entry->refresh();
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
