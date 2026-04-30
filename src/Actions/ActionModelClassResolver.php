<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Support\EloquentModelResolver;
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
        $model = EloquentModelResolver::fromClass($modelClass);
        if ($model === null) {
            return null;
        }

        return $model::class;
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
