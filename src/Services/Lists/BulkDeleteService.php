<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final readonly class BulkDeleteService
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
    public function delete(
        string $modelClass,
        array|string $records,
        ?array $schema,
        string $search = '',
        array $filters = [],
    ): int {
        $this->bulkQueryBuilder->assertValidEloquentModel(
            $modelClass,
            'Invalid model class for bulk delete.',
            'Bulk delete model must extend Eloquent Model.',
        );

        $selection = $this->bulkQueryBuilder->beginSelectionQuery(
            $modelClass,
            BulkSelectionQueryStrategy::Delete,
        );
        if (! $this->bulkQueryBuilder->constrainToSelection(
            $selection,
            $records,
            $schema,
            $search,
            $filters,
        )) {
            return 0;
        }

        $user = Auth::user();
        if ($user === null) {
            return 0;
        }

        $keyName = $selection->model->getKeyName();
        $authorizedIds = [];
        foreach ($selection->query->get() as $record) {
            if (! $record instanceof Model) {
                continue;
            }
            if ($this->authorizer->allows($user, 'delete', $modelClass, $record)) {
                $authorizedIds[] = (string) $record->getKey();
            }
        }
        if ($authorizedIds === []) {
            return 0;
        }

        return $modelClass::query()->whereIn($keyName, $authorizedIds)->delete();
    }
}
