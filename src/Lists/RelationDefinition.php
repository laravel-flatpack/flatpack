<?php

declare(strict_types=1);

namespace Flatpack\Lists;

final readonly class RelationDefinition
{
    public function __construct(
        public string $relation,
        public string $relationName,
        public string $relationValue,
    ) {}
}
