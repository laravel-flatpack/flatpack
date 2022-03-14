<?php

use Illuminate\Support\Arr;

if (! function_exists('getOption')) {
    /**
     * Get an option from the options array.
     *
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getOption($options, $key, $default = null)
    {
        return Arr::get($options, $key, $default);
    }
}
