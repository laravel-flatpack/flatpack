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

if (! function_exists('getErrors')) {
    /**
     * Get an option from the errors array.
     *
     * @param array $errors
     * @param string $key
     * @return mixed
     */
    function getErrors($errors, $key)
    {
        return Arr::get($errors, $key, []);
    }
}


if (! function_exists('groupComposition')) {
    /**
     * Return an array of fields grouped by "group" option.
     * Only consecutive fields with the same "group" option
     * will be grouped together.
     *
     * @param  array  $fields
     * @return array
     */
    function groupComposition($fields)
    {
        $result = [];
        $group = null;
        $i = 0;

        foreach ($fields as $key => $options) {
            if ($i === 0 || $key === 'tabs' || getOption($options, 'group') !== $group) {
                $result[][$key] = $options;
                $group = getOption($options, 'group');
            } else {
                $result[count($result) - 1][$key] = $options;
            }

            $group = getOption($options, 'group');
            $i++;
        }

        return $result;
    }
}
