<?php

namespace Flatpack\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlatpackAction
{
    /**
     * Entity name.
     *
     * @var string
     */
    protected $entity;

    /**
     * Model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Form fields values.
     *
     * @var array
     */
    protected $fields;

    /**
     * Model class name.
     *
     * @var string
     */
    protected $model;

    /**
     * Files to upload.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Selected keys.
     *
     * @var array
     */
    protected $selected = [];

    /**
     * Action successfully handled.
     *
     * @var bool
     */
    protected $success = false;

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
    protected $redirect = false;

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
    }

    /**
     * Add file to upload.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return $this
     */
    public function addFile($file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Add multiple files to upload.
     *
     * @param array $files
     * @return $this
     */
    public function addFiles($files)
    {
        $this->files = array_merge($this->files, $files);

        return $this;
    }

    /**
     * Action was successful.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success === true;
    }

    /**
     * Set action as successful.
     *
     * @return $this
     */
    protected function success()
    {
        $this->success = true;

        return $this;
    }

    /**
     * Set entry.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $entry
     * @return $this
     */
    public function setEntry(Model $entry = null)
    {
        $this->entry = $entry;
        $this->setIsMultiple(false);

        return $this;
    }

    /**
     * Get entry.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getEntry()
    {
        return $this->entry;
    }

    /**
     * Set form fields values.
     *
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields = [])
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get form fields values.
     *
     * @return array
     */
    protected function getFields()
    {
        return $this->fields;
    }

    /**
     * Set selected keys.
     *
     * @param array $selected
     * @return $this
     */
    public function setSelectedKeys($selected)
    {
        $this->selected = $selected;
        $this->setIsMultiple(true);

        return $this;
    }

    /**
     * Set whether action is multiple (bulk action).
     *
     * @param bool $isMultiple
     * @return $this
     */
    public function setIsMultiple($isMultiple)
    {
        $this->isMultiple = $isMultiple;

        return $this;
    }

    /**
     * Get whether action is multiple (bulk action).
     *
     * @return bool
     */
    public function isBulk()
    {
        return $this->isMultiple;
    }

    /**
     * Set redirect value.
     *
     * @param bool $redirect
     * @return $this
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * Determine if should redirect after performing the action.
     *
     * @return bool
     */
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

    /**
     * Get entity name for output.
     *
     * @param bool $plural - whether to return plural form.
     * @return string
     */
    protected function entityName($plural = false)
    {
        return $plural ? Str::plural($this->entity) : Str::singular($this->entity);
    }

    /**
     * Handle action.
     * --------------------------------
     * Override this method in your action class.
     * --------------------------------
     *
     * @throws \Exception
     * @return mixed
     */
    protected function handle()
    {
        throw new \Exception('Method not implemented: handle()');
    }

    /**
     * Determine if action is authorized.
     * --------------------------------
     * Override this method in your action class.
     * --------------------------------
     *
     * @throws \Exception
     * @return bool
     */
    protected function authorize()
    {
        throw new \Exception('Method not implemented: authorize()');

        return false;
    }
}
