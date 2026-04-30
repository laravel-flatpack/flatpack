<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

final readonly class ListQueryParams
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?string $search = null,
        public array $filters = [],
        public ?string $sortBy = null,
        public string $sortDirection = 'desc',
        public ?string $scope = null,
    ) {}
}
