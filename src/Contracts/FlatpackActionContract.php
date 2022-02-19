<?php

namespace Faustoq\Flatpack\Contracts;

interface FlatpackActionContract
{
    public function authorize();

    public function handle();
}
