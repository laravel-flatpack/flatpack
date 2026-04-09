<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Flatpack\Contracts\Composition\CompositionQuery;
use Override;

final readonly class DefaultCompositionQuery implements CompositionQuery
{
    public function __construct(
        private CompositionLoader $loader,
    ) {}

    #[Override]
    public function optional(string $entity, string $type): ?array
    {
        try {
            return $this->loader->load($entity, $type);
        } catch (CompositionNotFoundException) {
            return null;
        }
    }
}
