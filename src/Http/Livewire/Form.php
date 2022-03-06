<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithActions;
use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithFormValidation;
use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    use WithActions;
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
     * Field types excluded from validation and binding.
     *
     * @var array
     */
    private $excludedTypes = ['button', 'label', 'heading'];

    /**
     * Livewire component listeners.
     */
    protected $listeners = [
        'flatpack-imageuploader:updated' => 'saveImageUploaderState',
        'flatpack-imageuploader:error' => 'showImageUploaderError',
        'flatpack-taginput:change' => 'saveTagInputState',
        'flatpack-taginput:new-tag' => 'createRelatedTag',
        // 'editorjs-save:flatpack-editor' => 'saveEditorState',
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

    /**
     * Save the state of the image uploader.
     *
     * @param array $images
     * @return array
     */
    public function saveImageUploaderState($images)
    {
        dump($images);

        return $images;
    }

    /**
     * Show the image uploader error.
     *
     * @param string $message
     * @param array $images
     * @return void
     */
    public function showImageUploaderError($message, $images)
    {
        $this->notifyError($message);
    }

    /**
     * Save TagInput field state.
     *
     * @param  string $key Field key.
     * @param  string $tags Comma separated tags
     * @return array
     */
    public function saveTagInputState($key, $tags)
    {
        $this->fields[$key] = explode(',', $tags);

        return $this->fields[$key];
    }

    /**
     * TagInput related model creation.
     *
     * @return void
     */
    public function createRelatedTag($key, $data)
    {
        try {
            $fields = $this->getFormFields();
            $nameField = Arr::get($fields, "$key.relation.display", "name");

            $created = $this->createRelationship(
                $key,
                $nameField,
                $data
            );

            $this->emit(
                "flatpack-taginput:new-tag-created:{$key}",
                $created
            );
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    // public function saveEditorState($data)
    // {
    //     $this->fields['body'] = $data;
    // }

    public function action($method = 'cancel', $options = [])
    {
        // Cancel action
        if ($method === 'cancel') {
            return $this->goBack();
        }

        $redirect = Arr::get($options, 'redirect', false);

        try {
            $this->formErrors = [];

            // Form validation
            $this->validateForm($this->fields, $this->getFormFields());

            // Assign fields to model attributes
            $this->bindFieldsToModel();

            // Get action instance
            $action = $this->getAction($method)
                ->setEntry($this->entry)
                ->setFields($this->fields)
                ->setRedirect($redirect);

            // Execute action
            $action->run();

            if ($method === 'save') {
                $this->emit('flatpack-imageuploader:save', $this->entry);
            }

            // Action success notification
            if (method_exists($action, 'getMessage') && $action->isSuccess()) {
                $this->notifySuccess($action->getMessage());
            }

            // Action failure
            if (! $action->isSuccess()) {
                throw new \Exception('Action failed');
            }

            // Bind refreshed model attributes to fields
            $this->bindModelToFields();

            // if ($this->formType === 'create') {
            //     return $this->goToEditForm();
            // }

            if ($action->shouldRedirect()) {
                return $this->goBack();
            }
        } catch (ValidationException $e) {
            $this->formErrors = $e->errors();
            $this->notifyError($e->getMessage(), $this->formErrors);
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    /**
     * Bind model to fields.
     *
     * @return void
     */
    private function bindModelToFields()
    {
        foreach ($this->getFormFields() as $key => $options) {
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
            if ((isset($options['type']) && in_array($options['type'], $this->excludedTypes)) ||
                (isset($options['disabled']) && $options['disabled'] === true) ||
                (isset($options['readonly']) && $options['readonly'] === true) ||
                Str::contains($key, '_confirmation') ||
                $this->isRelationship($key) ||
                $this->fields[$key] === null) {
                continue;
            }

            $this->entry->{$key} = $this->fields[$key];
        }
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
        return $this->redirectTo(route('flatpack.list', [
            'entity' => $this->entity,
        ]));
    }

    /**
     * Notify success event.
     *
     * @param  string $message
     * @return \Livewire\Event
     */
    private function notifySuccess($message)
    {
        $this->emit('notify', [
            "type" => "success",
            "message" => $message,
        ]);
    }

    /**
     * Notify error event.
     *
     * @param  string $message
     * @param  array  $errors
     * @return \Livewire\Event
     */
    private function notifyError($error, $errors = [])
    {
        return $this->emit('notify', [
            "type" => "error",
            "message" => $error,
            "errors" => $errors,
        ]);
    }

    /**
     * Redirect event.
     *
     * @param  string $url
     * @return \Livewire\Event
     */
    private function redirectTo($url)
    {
        return $this->emit('redirect', [
            'url' => $url,
        ]);
    }
}
