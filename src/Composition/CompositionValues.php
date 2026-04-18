<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Schema\Generated\CompositionSchemaKeys;

/**
 * Reads common keys from parsed composition YAML.
 */
final readonly class CompositionValues
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function displayName(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        if (isset($data[CompositionSchemaKeys::LIST_ROOT_NAME]) && is_string($data[CompositionSchemaKeys::LIST_ROOT_NAME])) {
            return $data[CompositionSchemaKeys::LIST_ROOT_NAME];
        }

        if (isset($data['title']) && is_string($data['title'])) {
            return $data['title'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function modelClass(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        if (isset($data[CompositionSchemaKeys::LIST_ROOT_MODEL]) && is_string($data[CompositionSchemaKeys::LIST_ROOT_MODEL])) {
            return $data[CompositionSchemaKeys::LIST_ROOT_MODEL];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function icon(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        if (isset($data[CompositionSchemaKeys::LIST_ROOT_ICON]) && is_string($data[CompositionSchemaKeys::LIST_ROOT_ICON])) {
            return $data[CompositionSchemaKeys::LIST_ROOT_ICON];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function sortOrder(?array $data): int
    {
        if ($data === null) {
            return 99;
        }

        foreach ([
            CompositionSchemaKeys::LIST_ROOT_ORDER,
            CompositionSchemaKeys::LIST_ROOT_SORT_ORDER,
        ] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }

        return 99;
    }
}
