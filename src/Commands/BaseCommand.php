<?php

namespace Faustoq\Flatpack\Commands;

use Faustoq\Flatpack\Flatpack;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class BaseCommand extends GeneratorCommand
{
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/form.yaml');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespaceModel = $this->guessModelName();
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
     * Return the Flatpack directory name for the files.
     *
     * @return string
     */
    protected function flatpackDir($name)
    {
        return $this->laravel->basePath() . "/" .
            config('flatpack.directory', 'flatpack') . "/" .
            Flatpack::entityName($name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if ($this->hasOption('force') && $this->option('force') === true) {
            $this->files->delete($path);
        }

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->flatpackDir($name);
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param  string  $name
     * @return string|null
     */
    protected function guessModelName()
    {
        $name = $this->getNameInput();

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

        die();
    }

    /**
     * Delete files and folders.
    */
    protected function deleteFile($path)
    {
        $this->files->delete($path);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_OPTIONAL, 'Overwrite existing files'],
        ];
    }
}
