<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithActions;
use Flatpack\Traits\WithComposition;
use Flatpack\Traits\WithFormFields;
use Flatpack\Traits\WithFormValidation;
use Flatpack\Traits\WithRelationships;
use Livewire\Component;

class Form extends Component
{
    use WithActions;
    use WithComposition;
    use WithRelationships;
    use WithFormFields;
    use WithFormValidation;

    /**
     * Form fields values.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Change form fields.
     *
     * @var array
     */
    public $changedFields = [];

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
     * Form fields changed.
     *
     * @var bool
     */
    public $hasChanges = false;

    /**
     * Livewire component listeners.
     */
    protected $listeners = [
        'flatpack-editor:save' => 'saveEditorState',
        'flatpack-imageuploader:updated' => 'saveImageUploaderState',
        'flatpack-imageuploader:error' => 'showImageUploaderError',
        'flatpack-taginput:change' => 'saveTagInputState',
        'flatpack-taginput:create' => 'createRelatedEntity',
        'flatpack-relation:updated' => 'render',
        'flatpack-form-field:updated' => 'saveFieldsInputState',
    ];

    /**
     * Mount component.
     *
     * @return void
     */
    public function mount()
    {
        $this->formFields = $this->getAllCompositionFields();
        $this->bindModelToFields();
    }

    /**
     * Render component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('flatpack::components.form', [
            'toolbar' => $this->getComposition('toolbar'),
            'sidebar' => $this->getComposition('sidebar'),
            'header' => $this->getComposition('header'),
            'main' => $this->getMainComposition(),
        ]);
    }

    /**
     * On form field updated.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function updatedFields($value, $key)
    {
        $this->changedField($key, $value);

        $this->applyPreset($key, $value);

        $this->validateForm($this->fields, $this->onlyInputFields());
    }

    /**
     * Save the state of the image uploader.
     *
     * @param array $images
     * @return void
     */
    public function saveFieldsInputState($value, $key)
    {
        $this->changedField($key, $value);

        $this->applyPreset($key, $value);
    }

    /**
     * Save the state of the image uploader.
     *
     * @param array $images
     * @return array
     */
    public function saveImageUploaderState($key, $images)
    {
        $this->changedField($key, $images);

        return $this->fields[$key];
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
        $this->notifyError($message, $images);
    }

    /**
     * Save the state of the block-editor.
     *
     * @param string $key
     * @param array $data
     * @return array
     */
    public function saveEditorState($key, $data)
    {
        $this->changedField($key, json_encode($data));

        return $this->fields[$key];
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
        $tags = explode(',', $tags);

        $this->changedField($key, $tags);

        return $this->fields[$key];
    }

    /**
     * Related model creation.
     *
     * @return void
     */
    public function createRelatedEntity($key, $data)
    {
        try {
            $nameField = data_get($this->formFields, "$key.relation.display", "name");

            $created = $this->createRelationship(
                $key,
                $nameField,
                $data
            );

            $this->emit("flatpack-form:related-entity-created:{$key}", $created);
        } catch (\Exception $e) {
            $this->notifyError(__("Failed to create new {$key}"));
        }
    }

    /**
     * Perform validation and run a given action.
     *
     * @param  string $action
     * @param  array $options
     * @return void
     */
    public function action($action = 'cancel', $options = [])
    {
        if ($action === 'cancel') {
            return redirect()->route('flatpack.list', [
                'entity' => $this->entity,
            ]);
        }

        $this->beforeAction($action);

        try {
            // Get action instance
            $actionInstance = $this->getAction($action)
                ->setEntry($this->entry)
                ->setFields($this->fields)
                ->setRedirect(data_get($options, 'redirect', false));

            // Perform action
            $actionInstance->run();

            $this->afterAction($action);

            // Action success notification
            if (method_exists($actionInstance, 'getMessage') && $actionInstance->isSuccess()) {
                $this->notifySuccess($actionInstance->getMessage());
            }

            if ($actionInstance->shouldRedirect()) {
                return $this->goBack();
            }
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    /**
     * Run before action.
     *
     * @param  string $action
     * @return void
     */
    private function beforeAction($action)
    {
        $this->bindFieldsToModel();

        if ($action === 'save') {
            $this->validateForm($this->fields, $this->onlyInputFields());
        }
    }

    /**
     * Run after action.
     *
     * @param  string $method
     * @return void
     */
    private function afterAction($method)
    {
        $this->bindModelToFields();

        if ($method === 'save') {
            $this->emit('flatpack-form:saved', $this->fields, $this->entry->getKey());
            $this->emit('update_url', route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $this->entry->getKey(),
            ]));
            $this->formType = 'edit';
        }
    }

    /**
     * Client-side redirect to the list page.
     *
     * @return \Livewire\Event
     */
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
            'type' => 'success',
            'message' => $message,
        ]);
    }

    /**
     * Notify error event.
     *
     * @param  string $message
     * @param  array  $errors
     * @return \Livewire\Event
     */
    private function notifyError($message, $errors = [])
    {
        return $this->emit('notify', [
            'type' => 'error',
            'message' => $message,
            'errors' => $errors,
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
        return $this->emit('redirect', $url);
    }
}
