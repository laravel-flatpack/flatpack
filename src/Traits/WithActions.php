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
     * Initialise and return action class instance.
     *
     * @param  string $action
     * @throws \Flatpack\Exceptions\ActionNotFoundException
     * @throws \Exception
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

        // Initialise action class instance.
        $instance = new $actions[$action]($this->entity, $this->model);

        if (! $instance instanceof \Flatpack\Actions\FlatpackAction) {
            throw new \Exception(
                "Action class must extend \Flatpack\Actions\FlatpackAction: $action"
            );
        }

        if (! $instance instanceof \Flatpack\Contracts\FlatpackAction) {
            throw new \Exception(
                "Action class must implement \Flatpack\Contracts\FlatpackAction interface: $action"
            );
        }

        return $instance;
    }
}
