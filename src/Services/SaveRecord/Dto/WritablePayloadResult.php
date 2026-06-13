<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord\Dto;

final readonly class WritablePayloadResult
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
        public bool $hasDeferredRelationPayload,
    ) {}
}
