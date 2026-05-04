<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists\Normalization;

/**
 * Resolves the database column used for list reorder from list.yaml-style schema.
 *
 * Nullable when reorder is not configured ({@see \Flatpack\Http\Controllers\Concerns\HandlesReorderRecord} guards).
 */
final class ReorderColumnResolver
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public static function reorderColumnFromSchema(array $schema): ?string
    {
        $normalized = $schema['reorderableColumn'] ?? null;
        if (is_string($normalized) && trim($normalized) !== '') {
            return trim($normalized);
        }

        $reorderable = $schema['reorderable'] ?? null;
        if ($reorderable === true || $reorderable === 'true') {
            return 'sort_order';
        }
        if (is_string($reorderable) && trim($reorderable) !== '') {
            return trim($reorderable);
        }

        return null;
    }
}
