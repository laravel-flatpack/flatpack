<?php

namespace Faustoq\Flatpack\Actions;

use Illuminate\Database\Eloquent\Model;

class FlatpackAction
{
    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * Model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Form fields.
     *
     * @var array
     */
    public $fields;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Action successfully handled.
     *
     * @var bool
     */
    protected $success;

    /**
     * Redirect after action.
     *
     * @var bool
     */
    protected $redirect;

    public function __construct(string $entity, string $modelClass)
    {
        $this->entity = $entity;
        $this->model = $modelClass;
        $this->success = false;
        $this->redirect = false;
    }

    public function isSuccess()
    {
        return $this->success === true;
    }

    public function success()
    {
        $this->success = true;
        return $this;
    }

    public function setEntry(Model $model = null)
    {
        $this->entry = $model;
        return $this;
    }

    protected function getEntry()
    {
        return $this->entry;
    }

    public function setFields(array $fields = [])
    {
        $this->fields = $fields;
        return $this;
    }

    protected function getFields()
    {
        return $this->fields;
    }

    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
        return $this;
    }

    public function shouldRedirect()
    {
        return $this->isSuccess() && $this->redirect;
    }

    public function run()
    {
        $this->handle();

        $this->success();
    }
}
