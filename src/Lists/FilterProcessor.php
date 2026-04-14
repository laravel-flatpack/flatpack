<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Illuminate\Database\Eloquent\Builder;

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

                $value = trim((string) $raw);
                $out[$id] = ($value !== '' && in_array($value, $allowed, true))
                    ? $value
                    : null;

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
     */
    public static function applyToQuery(Builder $query, array $definitions, array $values): void
    {
        foreach ($definitions as $def) {
            $id = $def->id;
            $value = $values[$id] ?? null;
            if ($value === null) {
                continue;
            }

            if ($def->isSelect()) {
                if ($def->multiple && is_array($value)) {
                    if ($value !== []) {
                        $query->whereIn($id, $value);
                    }

                    continue;
                }
                if (is_string($value) && $value !== '') {
                    $query->where($id, $value);
                }

                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }
            if (($def->mode ?? 'exact') === 'from') {
                $query->whereDate($id, '>=', $value);
            } else {
                $query->whereDate($id, $value);
            }
        }
    }
}
