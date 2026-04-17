<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves Eloquent model class strings for authorization (used by ActionRuntime before handler authorize()).
 */
final class ActionModelClassResolver
{
    public function modelExists(?Model $model): bool
    {
        return $model instanceof Model && $model->exists;
    }

    /**
     * @return class-string<Model>|null
     */
    public function resolveEloquentModelClassOrNull(string $modelClass): ?string
    {
        $modelClass = trim($modelClass);
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass;
    }

    /**
     * Persisted rows use the instance class; otherwise the configured model class string (e.g. create).
     *
     * @return class-string<Model>|null
     */
    public function resolveForRecordAuthorize(string $modelClass, ?Model $model): ?string
    {
        if ($this->modelExists($model)) {
            /** @var Model $model */
            return $this->resolveEloquentModelClassOrNull($model::class);
        }

        return $this->resolveEloquentModelClassOrNull($modelClass);
    }
}
