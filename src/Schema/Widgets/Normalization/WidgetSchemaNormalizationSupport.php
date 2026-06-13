<?php

declare(strict_types=1);

namespace Flatpack\Schema\Widgets\Normalization;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\TableLikeWidgetNormalizer;

/**
 * Shared widget normalization helpers used by {@see WidgetSchemaNormalizer} and per-type normalizers
 * (status/data parsing, provider-resolved table columns).
 */
final readonly class WidgetSchemaNormalizationSupport
{
    public function __construct(
        private TableLikeWidgetNormalizer $tableLike,
    ) {}

    /**
     * @return 'warning'|'error'|'success'|'info'|'default'|null
     */
    public function normalizeWidgetStatusValue(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $status = trim($raw);
        if (! in_array($status, CompositionSchemaKeys::WIDGET_STATUS_VALUES, true)) {
            return null;
        }

        return $status;
    }

    /**
     * Normalizes column definitions returned from a provider-backed table widget (list or keyed map).
     *
     * @param  list<array<string, mixed>>|array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeProviderResolvedTableColumns(mixed $columns): array
    {
        return $this->tableLike->normalizeProviderResolvedTableColumns($columns);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeStatusWidgetData(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $normalized = [];
        $status = $this->normalizeWidgetStatusValue($raw['status'] ?? null);
        if ($status !== null) {
            $normalized['status'] = $status;
        }
        if (isset($raw['value']) && (is_string($raw['value']) || is_numeric($raw['value']))) {
            $normalized['value'] = is_string($raw['value']) ? $raw['value'] : (float) $raw['value'];
        }
        foreach (['context', 'updated_at', 'description'] as $key) {
            if (isset($raw[$key]) && is_string($raw[$key])) {
                $value = trim($raw[$key]);
                if ($value !== '') {
                    $normalized[$key] = $value;
                }
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
