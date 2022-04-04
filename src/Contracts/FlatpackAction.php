<?php

namespace Flatpack\Contracts;

interface FlatpackAction
{
    public function authorize();

    public function handle();
}
