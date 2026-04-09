<?php

declare(strict_types=1);

namespace Flatpack\Composition;

final readonly class ListComposition
{
    public function __construct(
        public ?string $name,
        public ?string $model,
        public ?string $icon,
        public int $order,
    ) {}
}
