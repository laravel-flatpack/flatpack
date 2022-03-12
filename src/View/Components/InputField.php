<?php

namespace Faustoq\Flatpack\View\Components;

class InputField extends FormField
{
    /**
     * Form field name.
     *
     * @var string
     */
    public $key;

    /**
     * Field type.
     *
     * @var string
     */
    public $type = 'text';

    /**
     * Field options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flatpack::components.input-field');
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
