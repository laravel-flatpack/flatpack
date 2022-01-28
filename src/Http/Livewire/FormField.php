<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Livewire\Component;

class FormField extends Component
{
    public $key = '';

    public $label = '';

    public $placeholder = '';

    public $span = 'full';

    public $type = 'text';

    public $form = ['create', 'edit'];

    public $options = [];

    public $items = [];

    public $readonly = false;

    public $disabled = false;

    public $required = false;

    public $value = null;

    public function render()
    {
        dump($this->value);
        dump($this->form);

        return view('flatpack::components.form-field');
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
