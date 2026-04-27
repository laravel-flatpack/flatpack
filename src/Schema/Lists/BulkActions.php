<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Support\SuccessRedirect;

final class BulkActions
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, action: string, icon: string, variant: string, success_message?: string, confirm?: bool, success_redirect?: string, enabled_if?: array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}, visible_if?: array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}}>
     */
    public static function fromSchema(?array $schema, ?CompositionDebugLog $debug = null): array
    {
        if ($schema === null) {
            return [];
        }

        $raw = $schema['bulk_actions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        $out = [];

        foreach ($raw as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $action = isset($definition['action']) ? trim((string) $definition['action']) : '';
            $icon = isset($definition['icon']) ? trim((string) $definition['icon']) : '';

            if ($label === '' || $action === '') {
                continue;
            }

            if (! self::isConfiguredAction($action)) {
                $id = is_string($key) && $key !== '' ? $key : (string) count($out);
                $debug?->add(sprintf(
                    'Action "%s" in bulk_actions.%s.action is not configured in flatpack.bulk_actions (omitted from UI).',
                    $action,
                    $id,
                ));

                continue;
            }

            $id = is_string($key) && $key !== '' ? $key : (string) count($out);

            $normalized = [
                'id' => $id,
                'label' => $label,
                'action' => $action,
                'icon' => $icon,
                'variant' => self::normalizeVariant($definition['variant'] ?? null),
            ];
            if (isset($definition['success_message'])) {
                $msg = trim((string) $definition['success_message']);
                if ($msg !== '') {
                    $normalized['success_message'] = $msg;
                }
            }
            if (($definition['confirm'] ?? null) === true) {
                $normalized['confirm'] = true;
            }
            if (isset($definition['success_redirect'])) {
                $sr = SuccessRedirect::normalize($definition['success_redirect']);
                if ($sr !== null) {
                    $normalized['success_redirect'] = $sr;
                }
            }
            if (isset($definition['enabled_if'])) {
                $enabledIf = self::normalizeInactiveUntil($definition['enabled_if']);
                if ($enabledIf !== null) {
                    $normalized['enabled_if'] = $enabledIf;
                }
            }
            if (isset($definition['visible_if'])) {
                $visibleIf = self::normalizeInactiveUntil($definition['visible_if']);
                if ($visibleIf !== null) {
                    $normalized['visible_if'] = $visibleIf;
                }
            }
            $out[] = $normalized;
        }

        return $out;
    }

    private static function isConfiguredAction(string $action): bool
    {
        $handlerClass = config("flatpack.bulk_actions.{$action}");

        return is_string($handlerClass) && trim($handlerClass) !== '';
    }

    private static function normalizeVariant(mixed $raw): string
    {
        if ($raw === null || ! is_string($raw)) {
            return 'outline';
        }

        $variant = trim($raw);
        if ($variant === '') {
            return 'outline';
        }

        if ($variant === 'primary') {
            return 'default';
        }

        if (in_array($variant, CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES, true)) {
            return $variant;
        }

        return 'outline';
    }

    /**
     * @return array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}|null
     */
    private static function normalizeInactiveUntil(mixed $raw): ?array
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
    private static function normalizeInactivePredicates(mixed $raw): ?array
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
