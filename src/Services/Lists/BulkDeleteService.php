<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final readonly class BulkDeleteService
{
    public function __construct(
        private BulkErasureService $bulkErasureService,
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  'all'|list<string|int>  $records
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $filters
     */
    public function delete(
        string $modelClass,
        array|string $records,
        ?array $schema,
        Authenticatable $user,
        string $search = '',
        array $filters = [],
        string $scope = '',
    ): int {
        return $this->bulkErasureService->erase(
            $modelClass,
            $records,
            $schema,
            $user,
            ability: 'delete',
            strategy: BulkSelectionQueryStrategy::Delete,
            invalidModelMessage: 'Invalid model class for bulk delete.',
            invalidModelTypeMessage: 'Bulk delete model must extend Eloquent Model.',
            search: $search,
            filters: $filters,
            scope: $scope,
        );
    }
}
