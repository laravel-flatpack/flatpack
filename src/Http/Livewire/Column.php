<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Rappasoft\LaravelLivewireTables\Views\Column as BaseColumn;

class Column extends BaseColumn
{
    public $width;

    public function __construct($text, $column)
    {
        parent::__construct($text, $column);
        $this->width = "auto";
    }

    public function setWidth(string $width)
    {
        $this->width = $width;
    }
}
