<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final readonly class BulkForceDeleteService
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
    public function forceDelete(
        string $modelClass,
        array|string $records,
        ?array $schema,
        Authenticatable $user,
        string $search = '',
        array $filters = [],
    ): int {
        return $this->bulkErasureService->erase(
            $modelClass,
            $records,
            $schema,
            $user,
            ability: 'forceDelete',
            strategy: BulkSelectionQueryStrategy::ForceDelete,
            invalidModelMessage: 'Invalid model class for bulk force-delete.',
            invalidModelTypeMessage: 'Bulk force-delete model must extend Eloquent Model.',
            force: true,
            search: $search,
            filters: $filters,
        );
    }
}
