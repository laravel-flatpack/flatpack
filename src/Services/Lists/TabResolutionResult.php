<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

final readonly class TabResolutionResult
{
    /**
     * @param  array{id: string, scope?: string, reorderable?: bool|string, reorderableColumn?: string, row_click?: string, columns?: mixed, filters?: mixed, bulk_actions?: mixed, default_sort?: array{key: string, direction: 'asc'|'desc'}, pagination?: bool}|null  $activeTab
     * @param  array<string, mixed>|null  $effectiveSchema
     */
    public function __construct(
        public ?array $activeTab,
        public ?array $effectiveSchema,
        public ?string $scope,
    ) {}
}
