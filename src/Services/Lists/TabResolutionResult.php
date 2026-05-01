<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Schema\Lists\NormalizedListSchema;

final readonly class TabResolutionResult
{
    /**
     * @param  array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool}|null  $activeTab
     */
    public function __construct(
        public ?array $activeTab,
        public ?NormalizedListSchema $effectiveSchema,
        public ?string $scope,
    ) {}
}
