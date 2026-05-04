<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Support\ReorderColumnResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ReorderActionHandler extends FlatpackActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'update',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        $modelClass = trim($context->modelClass);
        if (
            $modelClass === ''
            || ! class_exists($modelClass)
            || ! is_subclass_of($modelClass, Model::class)
        ) {
            throw new InvalidArgumentException('Cannot reorder records: model class is invalid.');
        }
        $id = $context->record ?? (is_object($context->model) ? (string) ($context->model->getKey() ?? '') : '');
        if ($id === '') {
            throw new ModelNotFoundException('Cannot reorder records: record id is missing.');
        }
        $newPosition = max(1, (int) $context->request->integer('position', 1));
        $column = $this->resolveReorderColumn($context);

        /** @var Model $prototype */
        $prototype = new $modelClass();
        $table = $prototype->getTable();
        $column = trim($column);

        if ($column === '' || ! Schema::hasColumn($table, $column)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot reorder records: column "%s" does not exist on table "%s".',
                $column !== '' ? $column : '(empty)',
                $table,
            ));
        }

        $scope = trim((string) ($context->scope ?? ''));

        return DB::transaction(function () use ($column, $id, $modelClass, $newPosition, $scope, $table): Model {
            $scopedQuery = $this->applyScope($modelClass::query(), $modelClass, $scope);

            /** @var Model $record */
            $record = (clone $scopedQuery)
                ->lockForUpdate()
                ->findOrFail($id);

            /** @var EloquentCollection<int, Model> $lockedRows */
            $lockedRows = (clone $scopedQuery)
                ->lockForUpdate()
                ->orderByRaw(
                    '(CASE WHEN '
                    . DB::connection()->getQueryGrammar()->wrap($column)
                    . ' IS NULL THEN 1 ELSE 0 END) ASC'
                )
                ->orderBy($column, 'asc')
                ->orderBy($record->getKeyName(), 'asc')
                ->get();

            $orderedIds = $lockedRows
                ->map(static fn (Model $row): string => (string) $row->getKey())
                ->values()
                ->all();
            $oldIndex = array_search($id, $orderedIds, true);
            if ($oldIndex === false) {
                throw new ModelNotFoundException('Cannot reorder records: scoped record is missing.');
            }
            $count = count($orderedIds);
            $targetIndex = max(0, min($count - 1, $newPosition - 1));
            if ($oldIndex === $targetIndex) {
                return $record;
            }

            $movedId = $orderedIds[$oldIndex];
            if ($movedId === null) {
                throw new ModelNotFoundException('Cannot reorder records: record id is missing from ordered set.');
            }
            array_splice($orderedIds, $oldIndex, 1);
            array_splice($orderedIds, $targetIndex, 0, [$movedId]);

            $idToPosition = [];
            foreach ($orderedIds as $index => $orderedId) {
                $idToPosition[$orderedId] = $index + 1;
            }

            $currentPositionById = [];
            foreach ($lockedRows as $row) {
                $rowId = (string) $row->getKey();
                $currentPositionById[$rowId] = (int) $row->getAttribute($column);
            }

            $changedIds = [];
            $caseFragments = [];
            $bindings = [];
            foreach ($idToPosition as $rowId => $nextPosition) {
                $currentPosition = $currentPositionById[$rowId] ?? null;
                if ($currentPosition === null || $currentPosition === $nextPosition) {
                    continue;
                }
                $changedIds[] = $rowId;
                $caseFragments[] = 'WHEN ? THEN ?';
                $bindings[] = $rowId;
                $bindings[] = $nextPosition;
            }

            if ($changedIds !== []) {
                $connection = DB::connection();
                $grammar = $connection->getQueryGrammar();
                $keyColumn = $record->getKeyName();
                $wrappedTable = $grammar->wrapTable($table);
                $wrappedKey = $grammar->wrap($keyColumn);
                $wrappedReorderColumn = $grammar->wrap($column);
                $inPlaceholders = implode(', ', array_fill(0, count($changedIds), '?'));
                $sql = sprintf(
                    'UPDATE %s SET %s = CASE %s %s ELSE %s END WHERE %s IN (%s)',
                    $wrappedTable,
                    $wrappedReorderColumn,
                    $wrappedKey,
                    implode(' ', $caseFragments),
                    $wrappedReorderColumn,
                    $wrappedKey,
                    $inPlaceholders,
                );
                $connection->update($sql, array_merge($bindings, $changedIds));
            }

            return $record->refresh();
        }, 3);
    }

    private function applyScope(Builder $query, string $modelClass, string $scope): Builder
    {
        if ($scope === '') {
            return $query;
        }

        $scopeMethod = 'scope' . ucfirst($scope);
        if (! method_exists($modelClass, $scopeMethod)) {
            return $query;
        }

        $query->{$scope}();

        return $query;
    }

    private function resolveReorderColumn(FlatpackActionContext $context): string
    {
        $column = ReorderColumnResolver::reorderColumnFromSchema($context->schema ?? []);
        if ($column === null || trim($column) === '') {
            throw new InvalidArgumentException('Cannot reorder records: reorderable column is not configured.');
        }

        return trim($column);
    }
}
