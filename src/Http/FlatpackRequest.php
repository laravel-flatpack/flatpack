<?php

declare(strict_types=1);

namespace Flatpack\Http;

use Illuminate\Http\Request;

/**
 * Shared helpers for detecting HTTP requests that belong to Flatpack routes.
 */
final class FlatpackRequest
{
    public static function matches(Request $request): bool
    {
        $prefix = trim((string) config('flatpack.http.prefix', 'flatpack'), '/');
        $path = trim($request->path(), '/');
        $underPrefix = $path === $prefix || str_starts_with($path, $prefix . '/');

        return $request->routeIs('flatpack.*')
            || $request->is($prefix, $prefix . '/*')
            || $request->segment(1) === $prefix
            || $underPrefix;
    }

    public static function isFlatpackLoginRoute(Request $request): bool
    {
        return $request->routeIs('flatpack.login');
    }
}
