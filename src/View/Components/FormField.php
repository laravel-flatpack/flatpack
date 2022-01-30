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
     * Forms where the field is used.
     *
     * @var array
     */
    public $forms = ['create', 'edit'];

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
     * Field current value.
     *
     * @var string|null
     */
    public $currentValue = null;

    /**
     * Field options.
     *
     * @var array
     */
    private $options = [];

    /**
     * Create a new FormField instance.
     *
     * @param string $key
     * @param array $options
     * @param \Illuminate\Database\Eloquent\Model $entry
     * @return void
     */
    public function __construct(string $key, array $options = [], $entry = null)
    {
        $this->key = $key;
        $this->options = $options;
        $this->entry = $entry;

        $this->initProps();
    }

    public function render()
    {
        return view('flatpack::components.form-field');
    }

    public function setValue($value)
    {
        if ($value instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->currentValue = $value->pluck('id')->toArray();
        } elseif ($value instanceof \Illuminate\Database\Eloquent\Model) {
            $this->currentValue = $value->getKey();
        } else {
            $this->currentValue = $value;
        }

        return $this;
    }

    /**
     * Dynamically initialize component props based on the options.
     */
    private function initProps(): void
    {
        // Common props
        $this->type = $this->getOption('type', 'text');
        $this->label = $this->getOption('label', '');
        $this->placeholder = $this->getOption('placeholder', '');
        $this->span = $this->getOption('span', 'full');
        $this->required = $this->getOption('required', false);
        $this->disabled = $this->getOption('disabled', false);
        $this->readonly = $this->getOption('readonly', false);

        // Select props
        if ($this->type === 'select') {
            $this->items = $this->getOption('items', []);
        }
        // Relation props
        if (in_array($this->type, [ 'relation', 'select', 'taginput' ])) {
            $field = $this->getOption('relation', $this->key);
            $display = $this->getOption('display', 'name');

            if ($this->isRelationship($field)) {
                $this->relationshipType = $this->getRelationshipType($field);
                $this->items = $this->getRelationshipItems($field, $display);
            }
        }
    }

    /**
     * Get option value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getOption($key, $default = null)
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
            'forms' => $this->forms,
            'items' => $this->items,
            'readonly' => $this->readonly,
            'disabled' => $this->disabled,
            'required' => $this->required,
            'value' => $this->value,
        ];
    }
}
