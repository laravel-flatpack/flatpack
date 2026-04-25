<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final class BulkDeleteService
{
    public function __construct(
        private readonly FlatpackAuthorizer $authorizer,
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
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('Invalid model class for bulk delete.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Bulk delete model must extend Eloquent Model.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $baseQuery = $model->newQuery();
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
