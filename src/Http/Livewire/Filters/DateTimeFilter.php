<?php

namespace Flatpack\Http\Livewire\Filters;

use DateTime;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class DateTimeFilter extends Filter
{
    public function validate($value)
    {
        if (DateTime::createFromFormat('Y-m-d\TH:i', $value) === false) {
            return false;
        }

        return $value;
    }

    public function isEmpty($value): bool
    {
        return $value === '';
    }

    public function render(DataTableComponent $component)
    {
        return view('flatpack::components.tools.filters.datetime', [
            'component' => $component,
            'filter' => $this,
        ]);
    }
}
