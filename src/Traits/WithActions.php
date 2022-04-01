<?php

namespace Flatpack\Traits;

use Flatpack\Exceptions\ActionNotFoundException;

trait WithActions
{
    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Initialise and return action class instance.
     *
     * @param  string $action
     * @return \Flatpack\Actions\FlatpackAction
     */
    protected function getAction($action)
    {
        $actions = config('flatpack.actions', []);

        if (! isset($actions[$action])) {
            throw new ActionNotFoundException("Action not found: $action");
        }

        if (! class_exists($actions[$action])) {
            throw new ActionNotFoundException("Action class does not exist: $actions[$action]");
        }

        $instance = new $actions[$action]($this->entity, $this->model);

        if (! $instance instanceof \Flatpack\Actions\FlatpackAction) {
            throw new ActionNotFoundException(
                "Action class must extend \Flatpack\Actions\FlatpackAction: $action"
            );
        }

        if (! $instance instanceof \Flatpack\Contracts\FlatpackActionContract) {
            throw new ActionNotFoundException(
                "Action class must implement \Flatpack\Contracts\FlatpackActionContract: $action"
            );
        }

        return $instance;
    }
}
