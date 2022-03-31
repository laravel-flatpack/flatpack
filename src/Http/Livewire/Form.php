<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithActions;
use Faustoq\Flatpack\Traits\WithComposition;
use Faustoq\Flatpack\Traits\WithFormFields;
use Faustoq\Flatpack\Traits\WithFormValidation;
use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Support\Arr;
use Livewire\Component;

class Form extends Component
{
    use WithActions;
    use WithComposition;
    use WithRelationships;
    use WithFormFields;
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
     * Form fields changed.
     *
     * @var bool
     */
    public $hasChanges = false;

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
        'flatpack-editor:save' => 'saveEditorState',
        'flatpack-imageuploader:updated' => 'saveImageUploaderState',
        'flatpack-imageuploader:error' => 'showImageUploaderError',
        'flatpack-taginput:change' => 'saveTagInputState',
        'flatpack-taginput:create' => 'createRelatedEntity',
        'flatpack-relation:updated' => 'render',
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

    public function updated($name, $value)
    {
        $this->setHasChanges($name, $value);

        $this->clearFieldError($name);
    }

    /**
     * Save the state of the image uploader.
     *
     * @param array $images
     * @return array
     */
    public function saveImageUploaderState($key, $images)
    {
        $this->setHasChanges($key, $images);

        $this->fields[$key] = $images;

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
        $this->notifyError($message);
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
        $this->setHasChanges($key, $data);

        $this->fields[$key] = json_encode($data);

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

        $this->setHasChanges($key, $tags);

        $this->fields[$key] = $tags;

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
            $fields = $this->getFormFields();
            $nameField = Arr::get($fields, "$key.relation.display", "name");

            $created = $this->createRelationship(
                $key,
                $nameField,
                $data
            );

            $this->emit("flatpack-form:related-entity-created:{$key}", $created);
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    /**
     * Perform validation and run a given action.
     *
     * @param  string $method
     * @param  array $options
     * @return void
     */
    public function action($method = 'cancel', $options = [])
    {
        $this->clearFormErrors();

        $this->beforeAction($method);

        try {
            // Assign fields to model attributes
            $this->bindFieldsToModel();

            $redirect = getOption($options, 'redirect', false);

            // Get action instance
            $action = $this->getAction($method)
                ->setEntry($this->entry)
                ->setFields($this->fields)
                ->setRedirect($redirect);

            $action->run();

            $this->afterAction($method);

            // Action success notification
            if (method_exists($action, 'getMessage') && $action->isSuccess()) {
                $this->notifySuccess($action->getMessage());
            }

            // Action failure
            if (! $action->isSuccess()) {
                $this->notifyError(__('flatpack::form.action_failed'));
            }

            // Bind refreshed model attributes to fields
            $this->bindModelToFields();

            if ($action->shouldRedirect()) {
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
        if ($action === 'save') {
            // Form validation
            $this->validateForm($this->fields, $this->getFormFields());
        }

        if ($action === 'cancel') {
            return $this->goBack();
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
        if ($method === 'save') {
            $this->entityId = $this->entry->getKey() ?? 'create';

            $this->emit('flatpack-form:saved', $this->fields, $this->entry->getKey());

            $this->emit('update_url', route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $this->entry->getKey(),
            ]));

            $this->formType = 'edit';
        }

        $this->hasChanges = false;
    }

    /**
     * Clean up field errors.
     *
     * @param string $key
     * @return void
     */
    private function clearFieldError($key)
    {
        $field = $this->fieldKeyName($key);

        unset($this->formErrors[$field]);
    }

    /**
     * Clean up all form errors.
     *
     * @return void
     */
    private function clearFormErrors()
    {
        $this->formErrors = [];
    }

    /**
     * Set hasChanges flag if field has changed.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function setHasChanges($key, $value)
    {
        $field = $this->fieldKeyName($key);

        $oldValue = $this->fields[$field];

        $this->hasChanges = $this->compareValues($oldValue, $value);
    }

    /**
     * Encode and compare two values.
     * Return true if they are different.
     *
     * @param  mixed $oldValue
     * @param  mixed $newValue
     * @return bool
     */
    private function compareValues($oldValue, $newValue)
    {
        return md5(json_encode($oldValue)) !== md5(json_encode($newValue));
    }

    /**
     * Return the clean field key name.
     *
     * @param  string $key
     * @return string
     */
    protected function fieldKeyName($name)
    {
        return str_replace('fields.', '', $name);
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
    private function notifyError($message, $errors = [])
    {
        return $this->emit('notify', [
            "type" => "error",
            "message" => $message,
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
        return $this->emit('redirect', $url);
    }
}
