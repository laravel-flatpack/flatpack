<?php

namespace Faustoq\Flatpack\View\Components;

use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

class FormField extends Component
{
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
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Form field name.
     *
     * @var string
     */
    public $key;

    /**
     * Field label.
     *
     * @var string
     */
    public $label = '';

    /**
     * Field placeholder text.
     *
     * @var string
     */
    public $placeholder = '';

    /**
     * Span of the field.
     *
     * @var int
     */
    public $span = 'full';

    /**
     * Field type.
     *
     * @var string
     */
    public $type = 'text';

    /**
     * Field errors.
     *
     * @var array
     */
    public $fieldErrors = [];

    /**
     * Field items (for select, radio, checkbox).
     *
     * @var array
     */
    public $items = [];

    /**
     * Relation options.
     *
     * @var mixed
     */
    public $relation = false;

    /**
     * Readonly field.
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * Disabled field.
     *
     * @var bool
     */
    public $disabled = false;

    /**
     * Required field.
     *
     * @var bool
     */
    public $required = false;

    /**
     * Field size.
     *
     * @var string
     */
    public $size = 'base';

    /**
     * Field current value.
     *
     * @var string|null
     */
    public $value = null;

    /**
     * Field options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Create a new FormField instance.
     *
     * @param string $key
     * @param array $options
     * @param string $entity
     * @param string $model
     * @param \Illuminate\Database\Eloquent\Model $entry
     * @param array $fieldErrors
     * @param string $formType
     * @return void
     */
    public function __construct(
        $key,
        $options = [],
        $entity = '',
        $model = '',
        $entry = null,
        $fieldErrors = [],
        $formType = 'create'
    ) {
        $this->key = $key;
        $this->options = $options;
        $this->entity = $entity;
        $this->model = $model;
        $this->entry = $entry;
        $this->fieldErrors = $fieldErrors;
        $this->formType = $formType;

        $this->initFormFieldProps();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flatpack::components.form-field');
    }

    /**
     * Dynamically initialize component props based on the options.
     */
    protected function initFormFieldProps(): void
    {
        $this->type = Str::lower($this->getFieldOption('type', 'text'));
        $this->label = $this->getFieldOption('label', '');
        $this->placeholder = $this->getFieldOption('placeholder', '');
        $this->span = $this->getFieldOption('span', 'full');
        $this->required = $this->getFieldOption('required', false);
        $this->disabled = $this->getFieldOption('disabled', false);
        $this->readonly = $this->getFieldOption('readonly', false);
        $this->value = $this->getFieldOption('value', optional($this->entry)->{$this->key});
        $this->items = $this->getFieldOption('items', []);
        $this->size = $this->getFieldOption('size', 'base');

        if (in_array($this->type, [ 'relation', 'select', 'taginput' ])) {
            $this->relation = $this->getFieldOption('relation', false);
        }
    }

    /**
     * Get option value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFieldOption($key, $default = null)
    {
        return getOption($this->getOptions(), $key, $default);
    }

    /**
     * Get options value.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return an array of field errors.
     *
     * @param ViewErrorBag $errors
     * @return array
     */
    public function getErrorMessages(ViewErrorBag $errors): array
    {
        $messages = $errors->getMessages();

        return array_filter($messages, fn ($key) => $key === $this->key, ARRAY_FILTER_USE_KEY);
    }
}
