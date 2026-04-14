<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Flatpack\Support\NavigationUrl;

/**
 * Normalizes optional {@code actions} from list.yaml into header buttons (label + prefixed href).
 */
final class ListHeaderActions
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
     * @return list<array{id: string, label: string, href: string, variant: string}>
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

        $prefix = trim((string) config('flatpack.prefix', 'flatpack'), '/');
        $allowExternalOrigins = (bool) config('flatpack.navigation.allow_external_origins', false);
        $out = [];

        foreach ($raw as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $url = isset($definition['url']) ? trim((string) $definition['url']) : '';
            $icon = isset($definition['icon']) ? trim((string) $definition['icon']) : '';
            if ($label === '' || $url === '') {
                continue;
            }
            $href = self::prefixedUrl($url, $prefix, $allowExternalOrigins);
            if ($href === '') {
                continue;
            }
            $id = is_string($key) && $key !== '' ? $key : (string) count($out);
            $out[] = [
                'id' => $id,
                'label' => $label,
                'icon' => $icon,
                'href' => $href,
                'variant' => self::normalizeVariant($definition['variant'] ?? null),
            ];
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
