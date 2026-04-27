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

final readonly class BulkForceDeleteService
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
    public function forceDelete(
        string $modelClass,
        array|string $records,
        ?array $schema,
        string $search = '',
        array $filters = [],
    ): int {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('Invalid model class for bulk force-delete.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Bulk force-delete model must extend Eloquent Model.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $baseQuery = $model->newQuery();
        if (method_exists($model, 'forceDelete')) {
            $baseQuery->withoutGlobalScope(SoftDeletingScope::class);
        }
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

        $authorizedIds = [];
        foreach ($baseQuery->get() as $record) {
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

        if (method_exists($model, 'forceDelete')) {
            return $modelClass::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereIn($keyName, $authorizedIds)
                ->forceDelete();
        }

        return $modelClass::query()->whereIn($keyName, $authorizedIds)->delete();
    }
}
