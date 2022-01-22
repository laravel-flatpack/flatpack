<?php

namespace Faustoq\Flatpack;

use Illuminate\Support\Str;

class Flatpack
{
    /**
     * Get the model class name by the entity name.
     *
     * @param  string $entity
     * @return string
     */
    public static function modelName($name = ''): string
    {
        return Str::studly(Str::singular($name));
    }

    /**
     * Get the model class by the entity name.
     *
     * @param  string $entity
     * @return string
     */
    public static function guessModelClass($name = ''): string
    {
        $modelClass = 'App\\' . self::modelName($name);

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        if (is_dir(app_path('Models/'))) {
            $modelClass = 'App\\Models\\' . self::modelName($name);

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return self::modelName($name);
    }

    /**
     * Get the entity name by the model class name.
     *
     * @param  string $entity
     * @return string
     */
    public static function entityName($name = ''): string
    {
        return Str::lower(Str::plural($name));
    }
}
