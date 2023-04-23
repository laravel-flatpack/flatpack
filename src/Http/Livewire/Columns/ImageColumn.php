<?php

namespace Flatpack\Http\Livewire\Columns;

use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn as Column;

class ImageColumn extends Column
{
    protected string $view = 'flatpack::includes.columns.image';
}
