<?php

declare(strict_types=1);

namespace Flatpack\Lists;

/** @deprecated Use Flatpack\Lists\HeaderActions instead. */
final class ListHeaderActions
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, icon: string, variant: string, href?: string, action?: string}>
     */
    public static function fromSchema(?array $schema): array
    {
        return HeaderActions::fromSchema($schema);
    }

    /**
     * Prefixes a path with the Flatpack route prefix. Absolute http(s) URLs are returned unchanged.
     */
    public static function prefixedUrl(string $url, string $prefix, bool $allowExternalOrigins = false): string
    {
        return HeaderActions::prefixedUrl($url, $prefix, $allowExternalOrigins);
    }

    public static function sanitizeHref(string $href, bool $allowExternalOrigins = false): string
    {
        return HeaderActions::sanitizeHref($href, $allowExternalOrigins);
    }
}
