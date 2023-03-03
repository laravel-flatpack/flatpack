<?php

namespace Flatpack\Exceptions;

use Exception;

class TemplateNotFoundException extends Exception
{
    private $entity;
    private $model;

    public function __construct($message = '', $entity = null, $model = null)
    {
        parent::__construct($message);

        $this->entity = $entity;
        $this->model = $model;
    }
}
