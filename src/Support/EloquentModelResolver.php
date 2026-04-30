<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Database\Eloquent\Model;

final class EloquentModelResolver
{
    public static function fromContext(FlatpackActionContext $context): ?Model
    {
        if ($context->model instanceof Model) {
            return $context->model;
        }

        return self::fromClass($context->modelClass);
    }

    public static function fromClass(string $modelClass): ?Model
    {
        $trimmed = trim($modelClass);
        if ($trimmed === '' || ! class_exists($trimmed)) {
            return null;
        }
        if (! is_subclass_of($trimmed, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $trimmed */
        return new $trimmed();
    }
}
