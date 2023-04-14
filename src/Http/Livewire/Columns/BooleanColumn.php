<?php

namespace Flatpack\Http\Livewire\Columns;

use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn as Column;

class BooleanColumn extends Column
{
    protected string $view = 'flatpack::includes.columns.boolean';
}
