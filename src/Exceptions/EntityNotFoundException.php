<?php

namespace Faustoq\Flatpack\Exceptions;

use Exception;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Faustoq\Flatpack\Exceptions\Solutions\GenerateFlatpackSolution;

class EntityNotFoundException extends Exception implements ProvidesSolution
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
        return new GenerateFlatpackSolution($this->entity, $this->model);
    }
}
