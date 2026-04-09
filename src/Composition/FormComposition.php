<?php

declare(strict_types=1);

namespace Flatpack\Composition;

final readonly class FormComposition
{
    public function __construct(
        public ?string $name,
        public ?string $model,
        public ?string $icon,
    ) {}
}
