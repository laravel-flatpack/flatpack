<?php

namespace Faustoq\Flatpack\View\Components;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

class FormField extends Component implements Arrayable
{
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
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function __construct(string $key, array $options = [], $model = null)
    {
        $this->key = $key;
        $this->options = $options;
        $this->model = $model;

        $this->initProps();
    }

    public function render()
    {
        return view('flatpack::components.form-field');
    }

    public function setValue($value)
    {
        $this->currentValue = $value;

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
        if ($this->type === 'relation' && method_exists($this->model, $this->key)) {
            $relation = $this->model->{$this->key}();

            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $initialValue = optional($this->model->{$this->key})->{$relation->getOwnerKeyName()};

                $this->setValue($initialValue);

                $this->items = $relation->getRelated()->get()->pluck(
                    $this->getOption('display', 'name'),
                    $relation->getOwnerKeyName()
                );
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
