<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Services\Navigation\NavigationUrl;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Support\SuccessRedirect;

/**
 * Normalizes optional {@code actions} from entity YAML (e.g. list and form) into header buttons.
 *
 * YAML {@code variant} values: {@see CompositionSchemaKeys::BUTTON_VARIANT_VALUES} ({@code primary} → {@code default}).
 */
final class HeaderActions
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, icon: string, variant: string, primary?: true, href?: string, action?: string, submit?: bool, success_message?: string, confirm?: bool, success_redirect?: string, enabled_if?: array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}, visible_if?: array{all?: list<array<string, mixed>>, any?: list<array<string, mixed>>, message?: string}, shortcut?: string}>
     */
    public static function fromSchema(?array $schema, ?CompositionDebugLog $debug = null): array
    {
        if ($schema === null) {
            return [];
        }

        $raw = $schema['actions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        $allowExternalOrigins = (bool) config('flatpack.ui.allow_external_navigation_urls', false);
        $out = [];

        foreach ($raw as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $action = isset($definition['action']) ? trim((string) $definition['action']) : '';
            $href = isset($definition['href']) ? trim((string) $definition['href']) : '';
            $icon = isset($definition['icon']) ? trim((string) $definition['icon']) : '';
            if ($label === '') {
                continue;
            }
            if (($action === '' && $href === '') || ($action !== '' && $href !== '')) {
                continue;
            }
            if ($href !== '') {
                $href = self::sanitizeHref($href, $allowExternalOrigins);
                if ($href === '') {
                    continue;
                }
            }
            $id = is_string($key) && $key !== '' ? $key : (string) count($out);
            $variantRaw = $definition['variant'] ?? null;
            $isPrimaryVariant = is_string($variantRaw) && trim($variantRaw) === 'primary';
            $explicitPrimary = ($definition['primary'] ?? null) === true;
            $normalized = [
                'id' => $id,
                'label' => $label,
                'icon' => $icon,
                'variant' => self::normalizeVariant($variantRaw),
            ];
            if ($explicitPrimary || $isPrimaryVariant) {
                $normalized['primary'] = true;
            }
            if ($action !== '') {
                if (! self::isConfiguredRecordAction($action)) {
                    $debug?->add(sprintf(
                        'Action "%s" in actions.%s.action is not configured in flatpack.actions (omitted from UI).',
                        $action,
                        $id,
                    ));

                    continue;
                }
                $normalized['action'] = $action;
                if (($definition['submit'] ?? null) === true) {
                    $normalized['submit'] = true;
                }
            }
            if ($href !== '') {
                $normalized['href'] = $href;
            }
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
                $enabledIf = InactiveConditionNormalizer::normalizeInactiveUntil($definition['enabled_if']);
                if ($enabledIf !== null) {
                    $normalized['enabled_if'] = $enabledIf;
                }
            }
            if (isset($definition['visible_if'])) {
                $visibleIf = InactiveConditionNormalizer::normalizeInactiveUntil($definition['visible_if']);
                if ($visibleIf !== null) {
                    $normalized['visible_if'] = $visibleIf;
                }
            }
            if (isset($definition['shortcut'])) {
                $shortcut = trim((string) $definition['shortcut']);
                if ($shortcut !== '') {
                    $normalized['shortcut'] = $shortcut;
                }
            }
            $out[] = $normalized;
        }

        return $out;
    }

    /**
     * Prefixes a path with the Flatpack route prefix. Absolute http(s) URLs are returned unchanged.
     */
    public static function prefixedUrl(string $url, string $prefix, bool $allowExternalOrigins = false): string
    {
        return NavigationUrl::sanitizeAndPrefix($url, $prefix, $allowExternalOrigins);
    }

    public static function sanitizeHref(string $href, bool $allowExternalOrigins = false): string
    {
        return NavigationUrl::sanitizeAndPrefix($href, '', $allowExternalOrigins);
    }

    /**
     * Always returns a valid Button variant.
     * Omitted / empty / non-string / unknown → {@code outline} so they do not match {@code primary} CTAs.
     */
    private static function normalizeVariant(mixed $raw): string
    {
        if ($raw === null) {
            return 'outline';
        }
        if (! is_string($raw)) {
            return 'outline';
        }
        $v = trim($raw);
        if ($v === '') {
            return 'outline';
        }
        if ($v === 'primary') {
            return 'default';
        }
        if (in_array($v, CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES, true)) {
            return $v;
        }

        return 'outline';
    }

    private static function isConfiguredRecordAction(string $action): bool
    {
        $handlerClass = config("flatpack.actions.{$action}");

        return is_string($handlerClass) && trim($handlerClass) !== '';
    }
}
