<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\NumberFilter as Filter;

class NumberFilter extends Filter
{
    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.number', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
