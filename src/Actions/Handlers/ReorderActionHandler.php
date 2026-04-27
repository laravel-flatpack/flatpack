<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Support\ReorderColumnResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ReorderActionHandler extends FlatpackActionHandler
{
    public function __construct(FlatpackAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
    }

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

        return DB::transaction(function () use ($column, $id, $modelClass, $newPosition): Model {
            /** @var Model $record */
            $record = $modelClass::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $oldPosition = (int) $record->getAttribute($column);
            $count = (int) $modelClass::query()->lockForUpdate()->count();
            $newPosition = max(1, min($count, $newPosition));

            if ($oldPosition === $newPosition) {
                return $record;
            }

            $quotedColumn = DB::connection()->getQueryGrammar()->wrap($column);

            if ($oldPosition < $newPosition) {
                $modelClass::query()
                    ->whereBetween($column, [$oldPosition + 1, $newPosition])
                    ->update([$column => DB::raw($quotedColumn . ' - 1')]);
            } else {
                $modelClass::query()
                    ->whereBetween($column, [$newPosition, $oldPosition - 1])
                    ->update([$column => DB::raw($quotedColumn . ' + 1')]);
            }

            $record->setAttribute($column, $newPosition);
            $record->save();

            return $record->refresh();
        }, 3);
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
