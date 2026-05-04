<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final readonly class BulkForceDeleteService
{
    public function __construct(
        private FlatpackAuthorizer $authorizer,
        private BulkQueryBuilder $bulkQueryBuilder,
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
        string $scope = '',
    ): int {
        $this->bulkQueryBuilder->assertValidEloquentModel(
            $modelClass,
            'Invalid model class for bulk force-delete.',
            'Bulk force-delete model must extend Eloquent Model.',
        );

        $selection = $this->bulkQueryBuilder->beginSelectionQuery(
            $modelClass,
            BulkSelectionQueryStrategy::ForceDelete,
        );
        if (! $this->bulkQueryBuilder->constrainToSelection(
            $selection,
            $records,
            $schema,
            $search,
            $filters,
            $scope,
        )) {
            return 0;
        }

        $keyName = $selection->model->getKeyName();
        $authorizedIds = [];
        foreach ($selection->query->get() as $record) {
            if (! $record instanceof Model) {
                continue;
            }
            if ($this->authorizer->allows($user, 'forceDelete', $modelClass, $record)) {
                $authorizedIds[] = (string) $record->getKey();
            }
        }
        if ($authorizedIds === []) {
            return 0;
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            return $modelClass::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereIn($keyName, $authorizedIds)
                ->forceDelete();
        }

        return $modelClass::query()->whereIn($keyName, $authorizedIds)->delete();
    }
}
