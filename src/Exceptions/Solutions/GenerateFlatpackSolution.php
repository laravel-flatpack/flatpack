<?php

namespace Flatpack\Exceptions\Solutions;

use Facade\IgnitionContracts\RunnableSolution;
use Illuminate\Support\Facades\Artisan;

class GenerateFlatpackSolution implements RunnableSolution
{
    private $entity;
    private $model;

    public function __construct($entity = null, $model = null)
    {
        $this->entity = $entity;
        $this->model = $model;
    }

    public function getSolutionTitle(): string
    {
        return "Flatpack configuration for '{$this->entity}' is missing.";
    }

    public function getDocumentationLinks(): array
    {
        return [
            'Usage documentation' => 'https://github.com/faustoq/laravel-flatpack#usage',
        ];
    }

    public function getSolutionActionDescription(): string
    {
        return "Generate the templates using `php artisan make:flatpack {$this->model}`";
    }

    public function getRunButtonText(): string
    {
        return "Generate files";
    }

    public function getSolutionDescription(): string
    {
        $directory = base_path(config('flatpack.directory', 'flatpack'));

        return "Check that {$this->model} model exists and create the required
            files in `{$directory}/{$this->entity}`.";
    }

    public function getRunParameters(): array
    {
        return [
            'name' => $this->model,
        ];
    }

    public function run(array $parameters = [])
    {
        Artisan::call('make:flatpack', $parameters);
    }
}
