<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Illuminate\Database\Eloquent\Model;

/**
 * Full form YAML merge for persistence and validation (tabs + sidebar into {@code fields}, normalized defs).
 *
 * Use instead of {@see \Flatpack\Schema\CompositionTabsMerge::form()} wherever submitted {@code values.*} keys
 * must resolve against the same {@code fields} map as the normalized form page (including sidebar-only fields).
 */
final class FormCompositionMergeForPersistence
{
    /**
     * @param  array<string, mixed>|null  $rawSchema
     * @return array<string, mixed>|null
     */
    public static function merge(?array $rawSchema, ?Model $model = null): ?array
    {
        if ($rawSchema === null) {
            return null;
        }

        $normalizer = app(FormSchemaNormalizer::class);
        $normalized = $normalizer->normalizedFormSchema(
            $rawSchema,
            null,
            $model !== null ? $model::class : null,
            $model,
        );

        return $normalized?->toArray();
    }
}
