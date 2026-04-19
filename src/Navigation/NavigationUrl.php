<?php

declare(strict_types=1);

namespace Flatpack\Navigation;

use Throwable;

final class NavigationUrl
{
    /**
     * Allows relative URLs and same-origin absolute http(s) URLs.
     * Denies javascript:, data:, protocol-relative URLs, and external origins by default.
     */
    public static function sanitize(string $url, bool $allowExternalOrigins = false): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, '//')) {
            return '';
        }

        if (preg_match('/^(javascript|data):/i', $normalized) === 1) {
            return '';
        }

        $scheme = parse_url($normalized, PHP_URL_SCHEME);
        if (! is_string($scheme) || $scheme === '') {
            return $normalized;
        }

        $scheme = mb_strtolower($scheme);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        if ($allowExternalOrigins || self::isSameOriginAsApp($normalized)) {
            return $normalized;
        }

        return '';
    }

    /**
     * Prefixes sanitized relative paths with the Flatpack route prefix.
     * Same-origin absolute URLs are preserved unchanged.
     */
    public static function sanitizeAndPrefix(string $url, string $prefix, bool $allowExternalOrigins = false): string
    {
        $sanitized = self::sanitize($url, $allowExternalOrigins);
        if ($sanitized === '') {
            return '';
        }

        $scheme = parse_url($sanitized, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            return $sanitized;
        }

        $path = '/' . ltrim($sanitized, '/');
        $normalizedPrefix = trim($prefix, '/');
        if ($normalizedPrefix === '') {
            return $path;
        }

        return '/' . $normalizedPrefix . $path;
    }

    private static function isSameOriginAsApp(string $url): bool
    {
        $appUrl = self::appUrl();
        if ($appUrl === '') {
            return false;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        $urlPort = parse_url($url, PHP_URL_PORT);
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $appPort = parse_url($appUrl, PHP_URL_PORT);

        if (! is_string($urlHost) || ! is_string($appHost) || $urlHost === '' || $appHost === '') {
            return false;
        }

        return mb_strtolower($urlHost) === mb_strtolower($appHost)
            && ($urlPort ?? self::defaultPort((string) parse_url($url, PHP_URL_SCHEME)))
                === ($appPort ?? self::defaultPort((string) parse_url($appUrl, PHP_URL_SCHEME)));
    }

    private static function defaultPort(string $scheme): ?int
    {
        return match (mb_strtolower($scheme)) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    private static function appUrl(): string
    {
        try {
            return (string) config('app.url', '');
        } catch (Throwable) {
            return '';
        }
    }
}
