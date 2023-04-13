<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class NumberFilter extends Filter
{
    public function validate($value)
    {
        return is_numeric($value) ? $value : false;
    }

    public function isEmpty($value): bool
    {
        return $value === '';
    }

    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.number', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
