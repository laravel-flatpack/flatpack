<?php
namespace Faustoq\Flatpack\Exceptions;

use Exception;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;

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
            ->setSolutionDescription("Did you generate the templates using `php artisan make:flatpack {$this->model}`?")
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/faustoq/laravel-flatpack',
            ]);
    }
}
