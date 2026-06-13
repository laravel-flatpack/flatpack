<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

final readonly class RelationDefinition
{
    public function __construct(
        public string $relation,
        public string $relationName,
        public string $relationValue,
    ) {}
}
