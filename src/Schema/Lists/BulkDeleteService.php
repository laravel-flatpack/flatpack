<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class BulkDeleteService
{
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
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('Invalid model class for bulk delete.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Bulk delete model must extend Eloquent Model.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $query = $model->newQuery();
        $keyName = $model->getKeyName();
        $selectAll = $records === 'all';

        if ($selectAll) {
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
            );
        } else {
            $ids = array_values(array_unique(array_filter(
                array_map(static fn (mixed $id): string => trim((string) $id), $records),
                static fn (string $id): bool => $id !== '',
            )));
            if ($ids === []) {
                return 0;
            }
            $query->whereIn($keyName, $ids);
        }

        return $query->delete();
    }
}
