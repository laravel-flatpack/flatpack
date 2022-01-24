<?php
namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Arr;

trait WithComposition
{
    public $composition = [];

    protected function getComposition($value)
    {
        return Arr::get($this->composition, $value, []);
    }
}
