<?php

declare(strict_types=1);

namespace Flatpack\Schema;

/**
 * Normalizes {@code enabled_if} / {@code visible_if} YAML blocks shared by header and bulk actions.
 */
final class InactiveConditionNormalizer
{
    /**
     * @return array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}|null
     */
    public static function normalizeInactiveUntil(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $all = self::normalizeInactivePredicates($raw['all'] ?? null);
        $any = self::normalizeInactivePredicates($raw['any'] ?? null);
        if ($all === null && $any === null) {
            return null;
        }

        $normalized = [];
        if ($all !== null) {
            $normalized['all'] = $all;
        }
        if ($any !== null) {
            $normalized['any'] = $any;
        }
        if (isset($raw['message'])) {
            $message = trim((string) $raw['message']);
            if ($message !== '') {
                $normalized['message'] = $message;
            }
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public static function normalizeInactivePredicates(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $predicates = [];
        foreach ($raw as $entry) {
            if (! is_array($entry) || count($entry) !== 1) {
                continue;
            }

            if (array_key_exists('form.dirty', $entry) && is_bool($entry['form.dirty'])) {
                $predicates[] = ['form.dirty' => $entry['form.dirty']];

                continue;
            }

            if (array_key_exists('form.mode_in', $entry) && is_array($entry['form.mode_in'])) {
                $modes = array_values(array_filter(
                    $entry['form.mode_in'],
                    static fn (mixed $mode): bool => in_array($mode, ['create', 'edit'], true),
                ));
                if ($modes !== []) {
                    $predicates[] = ['form.mode_in' => $modes];
                }

                continue;
            }

            if (array_key_exists('list.selection.min', $entry)) {
                $min = $entry['list.selection.min'];
                if (is_int($min) && $min >= 0) {
                    $predicates[] = ['list.selection.min' => $min];
                }

                continue;
            }

            if (array_key_exists('list.search_present', $entry) && is_bool($entry['list.search_present'])) {
                $predicates[] = ['list.search_present' => $entry['list.search_present']];

                continue;
            }

            if (array_key_exists('list.filters_applied', $entry) && is_bool($entry['list.filters_applied'])) {
                $predicates[] = ['list.filters_applied' => $entry['list.filters_applied']];

                continue;
            }

            if (array_key_exists('form.field_eq', $entry)) {
                $payload = $entry['form.field_eq'];
                if (! is_array($payload)) {
                    continue;
                }
                $field = isset($payload['field']) ? trim((string) $payload['field']) : '';
                if ($field === '' || ! array_key_exists('value', $payload)) {
                    continue;
                }
                $normalizedValue = self::tryNormalizeFieldEqValue($payload['value']);
                if ($normalizedValue === false) {
                    continue;
                }
                $predicates[] = [
                    'form.field_eq' => [
                        'field' => $field,
                        'value' => $normalizedValue,
                    ],
                ];

                continue;
            }

            if (array_key_exists('form.field_in', $entry)) {
                $payload = $entry['form.field_in'];
                if (! is_array($payload)) {
                    continue;
                }
                $field = isset($payload['field']) ? trim((string) $payload['field']) : '';
                $rawValues = $payload['values'] ?? null;
                if ($field === '' || ! is_array($rawValues)) {
                    continue;
                }
                $values = [];
                foreach ($rawValues as $value) {
                    if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
                        $values[] = $value;
                    }
                }
                if ($values === []) {
                    continue;
                }
                $predicates[] = [
                    'form.field_in' => [
                        'field' => $field,
                        'values' => $values,
                    ],
                ];

                continue;
            }

            if (array_key_exists('form.field_truthy', $entry)) {
                $field = self::normalizeSingleFieldPayload($entry['form.field_truthy']);
                if ($field !== null) {
                    $predicates[] = ['form.field_truthy' => ['field' => $field]];
                }

                continue;
            }

            if (array_key_exists('form.field_present', $entry)) {
                $field = self::normalizeSingleFieldPayload($entry['form.field_present']);
                if ($field !== null) {
                    $predicates[] = ['form.field_present' => ['field' => $field]];
                }

                continue;
            }

            if (array_key_exists('form.field_null', $entry)) {
                $field = self::normalizeSingleFieldPayload($entry['form.field_null']);
                if ($field !== null) {
                    $predicates[] = ['form.field_null' => ['field' => $field]];
                }
            }
        }

        return $predicates === [] ? null : $predicates;
    }

    private static function normalizeSingleFieldPayload(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }
        $field = isset($payload['field']) ? trim((string) $payload['field']) : '';

        return $field !== '' ? $field : null;
    }

    /**
     * @return array<mixed>|bool|float|int|string|null|false False when the value must be dropped.
     */
    private static function tryNormalizeFieldEqValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            return $value;
        }

        return false;
    }
}
