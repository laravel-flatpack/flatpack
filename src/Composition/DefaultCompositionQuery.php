<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Flatpack\Contracts\Composition\CompositionQuery;
use Override;

final class DefaultCompositionQuery implements CompositionQuery
{
    /** @var array<string, array<string, mixed>|null> */
    private array $cache = [];

    public function __construct(
        private readonly CompositionLoader $loader,
    ) {}

    #[Override]
    public function optional(string $entity, string $type): ?array
    {
        $scope = 'global';
        if (app()->bound('request')) {
            $request = app('request');
            if (is_object($request)) {
                $scope = 'request:' . spl_object_id($request);
            }
        }

        $cacheKey = $scope . ':' . $entity . ':' . $type;
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
}
