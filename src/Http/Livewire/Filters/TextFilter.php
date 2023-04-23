<?php

namespace Flatpack\Http\Livewire\Filters;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter as Filter;

class TextFilter extends Filter
{
    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.text-field', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
