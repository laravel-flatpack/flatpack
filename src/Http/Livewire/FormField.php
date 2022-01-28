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

    public $options = [];

    public $items = [];

    public $readonly = false;

    public $disabled = false;

    public $required = false;

    public $value = null;

    public function render()
    {
        return view('flatpack::components.form-field');
    }
}
