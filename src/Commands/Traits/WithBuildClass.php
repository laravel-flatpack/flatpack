<?php

namespace Flatpack\Commands\Traits;

use Flatpack\Facades\Flatpack;
use Illuminate\Support\Str;

trait WithBuildClass
{
    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespaceModel = $this->guessModelName($name);
        $modelName = Flatpack::modelName($name);
        $entityName = Flatpack::entityName($name);

        $replace = [
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            '{{ model }}' => $modelName,
            '{{model}}' => $modelName,
            '{{ entity }}' => $entityName,
            '{{entity}}' => $entityName,
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        return Str::afterLast(Str::afterLast($name, "\\"), "/");
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @return string|null
     */
    protected function guessModelName($name)
    {
        $modelClass = $this->qualifyModel($name);

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        if (is_dir(app_path('Models/'))) {
            $modelName = Str::studly($name);
            $modelClass = $this->rootNamespace()."Models\{$modelName}";

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        $this->output->error("Unable to determine model from name [{$name}].");
    }
}
