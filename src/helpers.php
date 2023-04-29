<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Flatpack versioned assets.
 *
 * @param string $file
 * @return string
 */
function flatpackAsset($file)
{
    return asset($file) . "?v=" . \Flatpack\Flatpack::VERSION;
}

/**
 * Get Flatpack url.
 *
 * @return string
 */
function flatpackUrl($url = '')
{
    $url = Str::of($url)->trim('/');

    $base = Str::of(config('flatpack.prefix', 'backend'))->trim('/');

    return Str::start($base->toString() . ((! $url->isEmpty()) ? (Str::start($url->toString(), '/')) : ''), '/');
}

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
