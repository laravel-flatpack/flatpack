<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter as Filter;

class SelectFilter extends Filter
{
    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.select', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
