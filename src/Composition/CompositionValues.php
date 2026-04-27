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

        if (isset($data[CompositionSchemaKeys::LIST_ROOT['name']]) && is_string($data[CompositionSchemaKeys::LIST_ROOT['name']])) {
            return $data[CompositionSchemaKeys::LIST_ROOT['name']];
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

        if (isset($data[CompositionSchemaKeys::LIST_ROOT['model']]) && is_string($data[CompositionSchemaKeys::LIST_ROOT['model']])) {
            return $data[CompositionSchemaKeys::LIST_ROOT['model']];
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

        if (isset($data[CompositionSchemaKeys::LIST_ROOT['icon']]) && is_string($data[CompositionSchemaKeys::LIST_ROOT['icon']])) {
            return $data[CompositionSchemaKeys::LIST_ROOT['icon']];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function navOrder(?array $data): int
    {
        if ($data === null) {
            return 99;
        }

        $key = CompositionSchemaKeys::LIST_ROOT['nav_order'];
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return (int) $data[$key];
        }

        return 99;
    }

    /**
     * Sidebar navigation group for this list (`main`, `secondary`, or `bottom`). Invalid or missing values default to `main`.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function listNavigationMenu(?array $data): string
    {
        if ($data === null) {
            return 'main';
        }

        $key = CompositionSchemaKeys::LIST_ROOT['menu'];
        if (! isset($data[$key]) || ! is_string($data[$key])) {
            return 'main';
        }

        $trimmed = trim($data[$key]);
        if ($trimmed === '') {
            return 'main';
        }

        if (in_array($trimmed, ['main', 'secondary', 'bottom'], true)) {
            return $trimmed;
        }

        return 'main';
    }
}
