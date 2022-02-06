<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithFormValidation;
use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    use WithComposition;
    use WithRelationships;
    use WithFormValidation;

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
     * Form field errors.
     *
     * @var array
     */
    public $formErrors = [];

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
            'main' => $this->getMainComposition(),
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

    public function action($method = 'save', $redirect = false)
    {
        // Cancel action
        if ($method === 'cancel') {
            return $this->goBack();
        }

        try {
            if (! method_exists($this->model, $method)) {
                throw new \Exception("Action not found: $method");
            }

            $this->formErrors = [];

            $this->validateForm($this->fields, $this->getFormFields());

            // Assign model properties
            $this->bindFieldsToModel();

            // Call model method
            $this->entry->{$method}();

            // Save relationships
            $this->syncFieldsToRelations();

            // Bind refreshed model to fields
            $this->bindModelToFields();

            // Redirect to edit form after create
            $this->notifySuccess(class_basename($this->entry) . " saved.");

            if ($this->formType === 'create') {
                return $this->goToEditForm();
            }

            if ($redirect) {
                return $this->goBack();
            }
        } catch (ValidationException $e) {
            $this->formErrors = $e->errors();
            $this->notifyError($e->getMessage(), $this->formErrors);
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    private function notifySuccess($message)
    {
        $this->emit('notify', [
            "type" => "success",
            "message" => $message,
        ]);
    }

    private function notifyError($error, $errors = [])
    {
        return $this->emit('notify', [
            "type" => "error",
            "message" => $error,
            "errors" => $errors
        ]);
    }

    /**
     * Bind model to fields.
     *
     * @return void
     */
    private function bindModelToFields()
    {
        $fields = $this->getFormFields();

        foreach ($fields as $key => $options) {
            if ($this->entry->$key instanceof \Illuminate\Support\Carbon) {
                $this->fields[$key] = $this->entry->$key->format('Y-m-d\TH:i:s');
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Collection) {
                $id = optional($this->relation($key))->getRelatedKeyName();
                $this->fields[$key] = $this->entry->$key->pluck($id)->toArray();
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Model) {
                $this->fields[$key] = $this->entry->$key->getKey();
            } else {
                $this->fields[$key] = $this->entry->$key;
            }
        }
    }

    /**
     * Bind fields to model.
     *
     * @return void
     */
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

    /**
     * Sync fields to relationships.
     *
     * @return void
     */
    private function syncFieldsToRelations()
    {
        $fields = $this->getFormFields();

        foreach ($fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                $this->isRelationship($key) === false) {
                continue;
            }

            $this->syncRelationship($key);
        }
        $this->entry->save();
        $this->entry->refresh();
    }

    private function goToEditForm()
    {
        return $this->redirectTo(route('flatpack.form', [
            'entity' => $this->entity,
            'id' => $this->entry->id,
        ]));
    }

    private function goBack()
    {
        return $this->redirectTo(route('flatpack.index', [
            'entity' => $this->entity,
        ]));
    }

    private function redirectTo($url)
    {
        return $this->emit('redirect', [
            'url' => $url,
        ]);
    }
}
