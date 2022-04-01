<?php

namespace Flatpack\Exceptions;

use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

class ModelNotFoundException extends Exception implements ProvidesSolution
{
    private $entity;
    private $model;

    public function __construct($message = '', $entity = null, $model = null)
    {
        parent::__construct($message);

        $this->entity = $entity;
        $this->model = $model;
    }

    /** @return  \Facade\IgnitionContracts\Solution */
    public function getSolution(): Solution
    {
        return BaseSolution::create("Flatpack could not map '{$this->entity}' to an existing model.")
            ->setSolutionDescription("Generate the templates using `php artisan make:flatpack {$this->model}`.")
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/laravel-flatpack/flatpack',
            ]);
    }
}
