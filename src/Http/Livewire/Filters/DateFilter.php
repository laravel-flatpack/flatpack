<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter as Filter;

class DateFilter extends Filter
{
    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.date', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
