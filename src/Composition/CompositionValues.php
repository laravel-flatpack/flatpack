<?php

declare(strict_types=1);

namespace Flatpack\Composition;

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

        if (isset($data['name']) && is_string($data['name'])) {
            return $data['name'];
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

        if (isset($data['model']) && is_string($data['model'])) {
            return $data['model'];
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

        if (isset($data['icon']) && is_string($data['icon'])) {
            return $data['icon'];
        }

        return null;
    }
}
