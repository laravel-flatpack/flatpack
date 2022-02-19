<?php

namespace Faustoq\Flatpack\Traits;

use Faustoq\Flatpack\Exceptions\ActionNotFoundException;

trait WithActions
{
    /**
     * Initialise and return action class instance.
     *
     * @param  string $action
     * @return \Faustoq\Flatpack\Actions\FlatpackAction
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

        if (! $instance instanceof \Faustoq\Flatpack\Actions\FlatpackAction) {
            throw new ActionNotFoundException(
                "Action class must extend \Faustoq\Flatpack\Actions\FlatpackAction: $action"
            );
        }

        if (! $instance instanceof \Faustoq\Flatpack\Contracts\FlatpackActionContract) {
            throw new ActionNotFoundException(
                "Action class must implement \Faustoq\Flatpack\Contracts\FlatpackActionContract: $action"
            );
        }

        return $instance;
    }
}
