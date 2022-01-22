<?php

namespace Faustoq\Flatpack;

use Illuminate\Support\Str;

class Flatpack
{
    public static function modelName($name = ''): string
    {
        return Str::studly(Str::singular($name));
    }

    public static function entityName($name = ''): string
    {
        return Str::lower(Str::plural($name));
    }
}
