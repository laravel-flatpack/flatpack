<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter as Filter;

class MultiSelectFilter extends Filter
{
    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.multi-select', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
