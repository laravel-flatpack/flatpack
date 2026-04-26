<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for record-level actions: provides authorizer(), canPerformAction(), and modelExists().
 * Concrete handlers implement authorize() and handle().
 */
abstract class FlatpackActionHandler implements FlatpackAction
{
    protected function authorizer(): FlatpackAuthorizer
    {
        return app(FlatpackAuthorizer::class);
    }

    protected function canPerformAction(
        Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool {
        return $this->authorizer()->allows(
            user: $user,
            ability: $ability,
            modelClass: $modelClass,
            model: $model,
        );
    }

    protected function modelExists(?Model $model): bool
    {
        return $model instanceof Model && $model->exists;
    }

    protected function resolveModel(FlatpackActionContext $context): ?Model
    {
        if ($context->model instanceof Model) {
            return $context->model;
        }

        $modelClass = trim($context->modelClass);
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return new $modelClass();
    }
}
