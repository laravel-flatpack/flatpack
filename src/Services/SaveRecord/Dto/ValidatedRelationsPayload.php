<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord\Dto;

final readonly class ValidatedRelationsPayload
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public ?array $schema,
        public array $values,
        public bool $shouldSync,
    ) {}
}
