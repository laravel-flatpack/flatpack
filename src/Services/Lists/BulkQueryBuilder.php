<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Schema\Lists\FilterProcessor;
use Flatpack\Schema\Lists\SchemaInspector;
use Flatpack\Schema\Lists\SearchApplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use InvalidArgumentException;

final readonly class BulkQueryBuilder
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function assertValidEloquentModel(
        string $modelClass,
        string $invalidClassMessage,
        string $notSubclassMessage,
    ): void {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException($invalidClassMessage);
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException($notSubclassMessage);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function beginSelectionQuery(string $modelClass, BulkSelectionQueryStrategy $strategy): BulkSelectionQuery
    {
        /** @var Model $model */
        $model = new $modelClass();
        $query = $model->newQuery();

        $query = match ($strategy) {
            BulkSelectionQueryStrategy::Delete => $query,
            BulkSelectionQueryStrategy::Restore => $query->withoutGlobalScope(SoftDeletingScope::class),
            BulkSelectionQueryStrategy::ForceDelete => method_exists($model, 'forceDelete')
                ? $query->withoutGlobalScope(SoftDeletingScope::class)
                : $query,
        };

        return new BulkSelectionQuery($model, $query);
    }

    /**
     * Applies list search/filters for select-all, or {@see whereIn} on primary keys for explicit IDs.
     *
     * @param  'all'|list<string|int>  $records
     */
    public function constrainToSelection(
        BulkSelectionQuery $selection,
        array|string $records,
        ?array $schema,
        string $search,
        array $filters,
    ): bool {
        $query = $selection->query;
        $model = $selection->model;
        $keyName = $model->getKeyName();

        if ($records === 'all') {
            $searchTerm = trim($search);
            if ($searchTerm !== '') {
                SearchApplier::apply(
                    $query,
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
                $query,
                $filterDefinitions,
                $normalizedFilters,
                $schema,
            );

            return true;
        }

        $ids = $this->normalizedExplicitIds($records);
        if ($ids === []) {
            return false;
        }
        $query->whereIn($keyName, $ids);

        return true;
    }

    /**
     * @param  list<string|int>  $records
     * @return list<string>
     */
    private function normalizedExplicitIds(array $records): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): string => trim((string) $id), $records),
            static fn (string $id): bool => $id !== '',
        )));
    }
}
