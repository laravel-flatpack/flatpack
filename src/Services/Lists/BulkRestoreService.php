<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Schema\Lists\FilterProcessor;
use Flatpack\Schema\Lists\SchemaInspector;
use Flatpack\Schema\Lists\SearchApplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final readonly class BulkRestoreService
{
    public function __construct(
        private FlatpackAuthorizer $authorizer,
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  'all'|list<string|int>  $records
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $filters
     */
    public function restore(
        string $modelClass,
        array|string $records,
        ?array $schema,
        string $search = '',
        array $filters = [],
    ): int {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('Invalid model class for bulk restore.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Bulk restore model must extend Eloquent Model.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        if (! method_exists($model, 'restore')) {
            return 0;
        }
        $baseQuery = $model->newQuery()->withoutGlobalScope(SoftDeletingScope::class);
        $keyName = $model->getKeyName();
        $selectAll = $records === 'all';

        if ($selectAll) {
            $searchTerm = trim($search);
            if ($searchTerm !== '') {
                SearchApplier::apply(
                    $baseQuery,
                    SchemaInspector::searchableColumnDefinitions($schema),
                    $searchTerm,
                );
            }

            $filterDefinitions = SchemaInspector::filterDefinitions($schema);
            $normalizedFilters = FilterProcessor::normalizeValues(
                $filterDefinitions,
                $filters,
            );
            FilterProcessor::applyToQuery(
                $baseQuery,
                $filterDefinitions,
                $normalizedFilters,
            );
        } else {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn (mixed $id): string => trim((string) $id), $records),
                static fn (string $id): bool => $id !== '',
            )));
            if ($ids === []) {
                return 0;
            }
            $baseQuery->whereIn($keyName, $ids);
        }

        $user = Auth::user();
        if ($user === null) {
            return 0;
        }

        $authorizedRecords = [];
        foreach ($baseQuery->get() as $record) {
            if (! $record instanceof Model) {
                continue;
            }
            if (! method_exists($record, 'trashed') || $record->trashed() !== true) {
                continue;
            }
            if ($this->authorizer->allows($user, 'restore', $modelClass, $record)) {
                $authorizedRecords[] = $record;
            }
        }
        if ($authorizedRecords === []) {
            return 0;
        }

        $restored = 0;
        foreach ($authorizedRecords as $record) {
            if (! method_exists($record, 'restore')) {
                continue;
            }
            if ($record->restore()) {
                $restored++;
            }
        }

        return $restored;
    }
}
