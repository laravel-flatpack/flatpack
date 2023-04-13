<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TextFilter extends Filter
{
    public function validate($value)
    {
        if ($this->hasConfig('maxlength')) {
            return strlen($value) <= $this->getConfig('maxlength') ? $value : false;
        }

        return strlen($value) ? $value : false;
    }

    public function isEmpty($value): bool
    {
        return $value === '';
    }

    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.text-field', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
