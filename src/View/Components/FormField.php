<?php

namespace Faustoq\Flatpack\View\Components;

use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

class FormField extends Component implements Arrayable
{
    use WithRelationships;

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
     * Relationship type.
     *
     * @var mixed
     */
    public $relationshipType = null;

    /**
     * Can create new related items.
     *
     * @var mixed
     */
    public $canCreate = false;

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
     * @param \Illuminate\Database\Eloquent\Model $entry
     * @param array $fieldErrors
     * @param string $formType
     * @return void
     */
    public function __construct(
        $key,
        $options = [],
        $entry = null,
        $fieldErrors = [],
        $formType = 'create'
    ) {
        $this->key = $key;
        $this->options = $options;
        $this->entry = $entry;
        $this->fieldErrors = $fieldErrors;
        $this->formType = $formType;

        $this->initProps();
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
    protected function initProps(): void
    {
        $this->type = $this->getOption('type', 'text');
        $this->label = $this->getOption('label', '');
        $this->placeholder = $this->getOption('placeholder', '');
        $this->span = $this->getOption('span', 'full');
        $this->required = $this->getOption('required', false);
        $this->disabled = $this->getOption('disabled', false);
        $this->readonly = $this->getOption('readonly', false);
        $this->value = $this->getOption('value', $this->entry->{$this->key});
        $this->items = $this->getOption('items', []);
        $this->size = $this->getOption('size', 'base');

        if (in_array($this->type, [ 'relation', 'select', 'taginput' ])) {
            $this->initRelationship();
        }
    }

    protected function initRelationship(): void
    {
        $key = $this->getOption('relation.name', $this->key);

        if ($this->isRelationship($key)) {
            $this->relationshipType = $this->getRelationshipType($key);
        }
    }

    /**
     * Get option value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return Arr::get($this->options, $key, $default);
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
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'span' => $this->span,
            'type' => $this->type,
            'items' => $this->items,
            'readonly' => $this->readonly,
            'disabled' => $this->disabled,
            'required' => $this->required,
            'value' => $this->value,
        ];
    }
}
