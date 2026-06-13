<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Illuminate\Database\Eloquent\Model;

final class ModelKeyResolver
{
    public function resolve(?string $modelClass): string
    {
        if (! is_string($modelClass) || $modelClass === '') {
            return 'id';
        }

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return 'id';
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = trim($model->getKeyName());

        return $keyName !== '' ? $keyName : 'id';
    }
}
