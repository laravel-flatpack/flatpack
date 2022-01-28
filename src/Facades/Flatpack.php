<?php

namespace Faustoq\Flatpack\Facades;

use Illuminate\Support\Facades\Facade;

class Flatpack extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'flatpack';
    }
}
