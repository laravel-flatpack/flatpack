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
            }
        }

        return $predicates === [] ? null : $predicates;
    }
}
