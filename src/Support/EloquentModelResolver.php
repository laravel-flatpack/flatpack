<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Actions\ActionContext;
use Illuminate\Database\Eloquent\Model;

final class EloquentModelResolver
{
    public static function fromContext(ActionContext $context): ?Model
    {
        if ($context->model instanceof Model) {
            return $context->model;
        }

        return self::fromClass($context->modelClass);
    }

    /**
     * @return class-string<Model>|null
     */
    public static function validEloquentClassOrNull(string $modelClass): ?string
    {
        $trimmed = trim($modelClass);
        if ($trimmed === '' || ! class_exists($trimmed)) {
            return null;
        }
        if (! is_subclass_of($trimmed, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $trimmed */
        return $trimmed;
    }

    public static function fromClass(string $modelClass): ?Model
    {
        $class = self::validEloquentClassOrNull($modelClass);
        if ($class === null) {
            return null;
        }

        return new $class();
    }
}
