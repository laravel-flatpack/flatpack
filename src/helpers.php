<?php

use Illuminate\Support\Arr;

if (! function_exists('flatpackAsset')) {
    /**
     * Flatpack versioned assets.
     *
     * @param string $file
     * @return string
     */
    function flatpackAsset($file)
    {
        return (app()->environment('local')) ?
            asset($file) :
            (asset($file) . "?v=" . \Flatpack\Flatpack::VERSION);
    }
}

if (! function_exists('flatpackUrl')) {
    /**
     * Get Flatpack home url.
     *
     * @return string
     */
    function flatpackUrl()
    {
        return "/" . config('flatpack.prefix', 'backend');
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
            if ($i === 0 || $key === 'tabs' || data_get($options, 'group') !== $group) {
                $result[][$key] = $options;
                $group = data_get($options, 'group');
            } else {
                $result[count($result) - 1][$key] = $options;
            }

            $group = data_get($options, 'group');
            $i++;
        }

        return $result;
    }
}
