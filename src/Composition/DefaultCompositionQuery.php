<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Flatpack\Contracts\Composition\CompositionQuery;
use Illuminate\Http\Request;
use Override;

final class DefaultCompositionQuery implements CompositionQuery
{
    /**
     * Fallback memoization when no HTTP request is available (e.g. console, some unit tests).
     * Per-request caching uses {@see Request::$attributes} so Octane / long-lived workers do not
     * retain stale compositions across requests (unlike `spl_object_id` keys on a singleton service).
     *
     * @var array<string, array<string, mixed>|null>
     */
    private array $cache = [];

    public function __construct(
        private readonly CompositionLoader $loader,
    ) {}

    /**
     * Clears non-request fallback cache (e.g. after tests or rare CLI batches). HTTP-scoped entries
     * live on the current request and are discarded with it.
     */
    public function reset(): void
    {
        $this->cache = [];
    }

    #[Override]
    public function optional(string $entity, string $type): ?array
    {
        if (function_exists('app') && app()->bound('request')) {
            $resolved = app('request');
            if ($resolved instanceof Request) {
                $key = $this->requestCacheKey($entity, $type);
                if ($resolved->attributes->has($key)) {
                    /** @var array<string, mixed>|null */
                    return $resolved->attributes->get($key);
                }

                try {
                    $loaded = $this->loader->load($entity, $type);
                } catch (CompositionNotFoundException) {
                    $loaded = null;
                }
                $resolved->attributes->set($key, $loaded);

                return $loaded;
            }
        }

        $cacheKey = 'global:' . $entity . ':' . $type;
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        try {
            $this->cache[$cacheKey] = $this->loader->load($entity, $type);

            return $this->cache[$cacheKey];
        } catch (CompositionNotFoundException) {
            $this->cache[$cacheKey] = null;

            return null;
        }
    }

    private function requestCacheKey(string $entity, string $type): string
    {
        return 'flatpack.composition.optional.' . $entity . '.' . $type;
    }
}
