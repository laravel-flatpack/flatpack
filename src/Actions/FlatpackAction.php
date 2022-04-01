<?php

namespace Flatpack\Actions;

use Illuminate\Auth\Access\AuthorizationException;
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
     * Files to upload.
     * \Illuminate\Http\UploadedFile
     *
     * @var array
     */
    public $files;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Selected keys.
     *
     * @var array
     */
    protected $selected;

    /**
     * Action successfully handled.
     *
     * @var bool
     */
    protected $success;

    /**
     * Action performed on multiple entries.
     *
     * @var bool
     */
    protected $isMultiple;

    /**
     * Redirect after action.
     *
     * @var bool
     */
    protected $redirect;

    /**
     * FlatpackAction constructor.
     *
     * @param string $entity
     * @param string $modelClass
     */
    public function __construct(string $entity, string $modelClass)
    {
        $this->entity = $entity;
        $this->model = $modelClass;
        $this->success = false;
        $this->redirect = false;
        $this->files = [];
        $this->selected = [];
    }

    public function addFile($file)
    {
        $this->files[] = $file;

        return $this;
    }

    public function addFiles($files)
    {
        $this->files = array_merge($this->files, $files);

        return $this;
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

    public function setEntry(Model $entry = null)
    {
        $this->entry = $entry;
        $this->isMultiple = false;

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

    public function setSelectedKeys($selected)
    {
        $this->selected = $selected;
        $this->isMultiple = true;

        return $this;
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

    /**
     * Execute action by calling the handle method.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return void
     */
    public function run()
    {
        if (! $this->authorize()) {
            throw new AuthorizationException(__("You are not authorized to perform this action."));
        }

        $result = $this->handle();

        $this->success();

        return $result;
    }
}
