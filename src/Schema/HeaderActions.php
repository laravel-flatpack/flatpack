<?php

declare(strict_types=1);

namespace Flatpack\Schema;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\NavigationUrl;
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
     * @return list<array{id: string, label: string, icon: string, variant: string, href?: string, action?: string, success_message?: string, confirm?: bool, success_redirect?: string, disable_until_dirty?: true, shortcut?: string}>
     */
    public static function fromSchema(?array $schema): array
    {
        if ($schema === null) {
            return [];
        }

        $raw = $schema['actions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        $globalDisableUntilDirty = (bool) config('flatpack.forms.disable_actions_until_dirty', false);
        $allowExternalOrigins = (bool) config('flatpack.navigation.allow_external_origins', false);
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
            $normalized = [
                'id' => $id,
                'label' => $label,
                'icon' => $icon,
                'variant' => self::normalizeVariant($definition['variant'] ?? null),
            ];
            if ($action !== '') {
                $normalized['action'] = $action;
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
            if ($globalDisableUntilDirty || (($definition['disable_until_dirty'] ?? false) === true)) {
                $normalized['disable_until_dirty'] = true;
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
}
