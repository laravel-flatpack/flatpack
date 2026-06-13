<?php

declare(strict_types=1);

namespace Flatpack\Schema;

final class YamlSchemaHelper
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function readString(array $data, string $snakeKey, string $camelKey): string
    {
        foreach ([$snakeKey, $camelKey] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                return trim($data[$key]);
            }
        }

        return '';
    }
}
