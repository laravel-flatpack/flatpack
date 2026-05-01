<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class FilterProcessor
{
    /**
     * @param  list<FilterDefinition>  $definitions
     * @param  array<string, mixed>  $input
     * @return array<string, string|list<string>|null>
     */
    public static function normalizeValues(array $definitions, array $input): array
    {
        $out = [];
        foreach ($definitions as $def) {
            $id = $def->id;
            $raw = $input[$id] ?? null;
            if ($raw === null) {
                $out[$id] = null;

                continue;
            }

            if ($def->isSelect()) {
                $allowed = array_column($def->options, 'value');
                if ($def->multiple) {
                    $values = is_array($raw) ? $raw : [$raw];
                    $normalized = [];
                    foreach ($values as $value) {
                        $v = trim((string) $value);
                        if ($v === '' || ! in_array($v, $allowed, true)) {
                            continue;
                        }
                        $normalized[] = $v;
                    }
                    $out[$id] = $normalized !== [] ? array_values(array_unique($normalized)) : null;

                    continue;
                }

                // Single select: accept a string, or an array (e.g. repeated query keys) and use the first allowed value.
                $candidates = is_array($raw) ? $raw : [$raw];
                $value = null;
                foreach ($candidates as $candidate) {
                    $v = trim((string) $candidate);
                    if ($v !== '' && in_array($v, $allowed, true)) {
                        $value = $v;
                        break;
                    }
                }
                $out[$id] = $value;

                continue;
            }

            $value = trim((string) $raw);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $out[$id] = null;

                continue;
            }
            $out[$id] = $value;
        }

        return $out;
    }

    /**
     * @param  list<FilterDefinition>  $definitions
     * @param  array<string, string|list<string>|null>  $values
     * @param  array<string, mixed>|null  $schema  Used to validate filter column ids against the list schema.
     */
    public static function applyToQuery(
        Builder $query,
        array $definitions,
        array $values,
        ?array $schema = null,
    ): void {
        $grammar = $query->getQuery()->getGrammar();
        $allowedColumns = self::allowedFilterColumnIds($query, $schema, $definitions);

        foreach ($definitions as $def) {
            $id = $def->id;
            if (! in_array($id, $allowedColumns, true)) {
                continue;
            }

            if (! preg_match('/^[A-Za-z0-9_]+$/', $id)) {
                continue;
            }
            $wrapped = DB::raw($grammar->wrap($id));
            $value = $values[$id] ?? null;
            if ($value === null) {
                continue;
            }

            if ($def->isSelect()) {
                if ($def->multiple && is_array($value)) {
                    if ($value !== []) {
                        $query->whereIn($wrapped, $value);
                    }

                    continue;
                }
                if (is_string($value) && $value !== '') {
                    $query->where($wrapped, '=', $value);
                }

                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }
            if (($def->mode ?? 'exact') === 'from') {
                $query->whereDate($wrapped, '>=', $value);
            } else {
                $query->whereDate($wrapped, '=', $value);
            }
        }
    }

    /**
     * Column ids must appear on the schema column map and/or be declared by a built-in filter
     * definition (filters-only keys such as {@code created_at} without a columns entry).
     *
     * @param  list<FilterDefinition>  $definitions
     * @return list<string>
     */
    private static function allowedFilterColumnIds(
        Builder $query,
        ?array $schema,
        array $definitions,
    ): array {
        $fromColumns = SchemaInspector::columnKeys($schema);
        $fromDefinitions = array_map(
            static fn (FilterDefinition $def): string => $def->id,
            $definitions,
        );
        $allowed = array_values(array_unique(array_merge($fromColumns, $fromDefinitions)));
        $keyName = $query->getModel()->getKeyName();
        if ($keyName !== '' && ! in_array($keyName, $allowed, true)) {
            return array_merge([$keyName], $allowed);
        }

        return $allowed;
    }
}
