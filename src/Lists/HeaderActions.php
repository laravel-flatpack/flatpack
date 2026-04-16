<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Flatpack\Support\NavigationUrl;

/**
 * Normalizes optional {@code actions} from list.yaml into header buttons.
 */
final class HeaderActions
{
    /**
     * Matches `buttonVariants` in resources/js/components/ui/button.tsx.
     * - Omitted {@code variant} → {@code outline} (secondary actions).
     * - {@code primary} → {@code default} (main filled / brand CTA).
     */
    private const array ALLOWED_VARIANTS = [
        'default',
        'outline',
        'secondary',
        'ghost',
        'destructive',
        'link',
    ];

    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, icon: string, variant: string, href?: string, action?: string, success_message?: string, confirm?: bool}>
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
        if (in_array($v, self::ALLOWED_VARIANTS, true)) {
            return $v;
        }

        return 'outline';
    }
}
