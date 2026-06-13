<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

final readonly class SearchDefinition
{
    private function __construct(
        public string $kind,
        public ?string $id,
        public ?string $relation,
        public ?string $relationName,
    ) {}

    public static function forColumn(string $id): self
    {
        return new self('column', $id, null, null);
    }

    public static function forRelation(string $relation, string $relationName): self
    {
        return new self('relation', null, $relation, $relationName);
    }
}
