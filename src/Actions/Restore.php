<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;
use Flatpack\Exceptions\ActionNotFoundException;

class Restore extends FlatpackAction implements FlatpackActionContract
{
    /**
     * Action authorization.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get success message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->isBulk() ?
            __(':count :entity restored.', [
                'count' => count($this->selected),
                'entity' => $this->entityName(true),
            ]) :
            __(':entity restored.', [
                'entity' => $this->entityName(),
            ]);
    }

    /**
     * Get confirmation message.
     *
     * @return string
     */
    public function getConfirmationMessage()
    {
        return __('Are you sure you want to restore the selected :entity?', [
            'entity' => $this->entityName(true),
        ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model))) {
            throw new ActionNotFoundException(
                "Empty Trash action is only supported by models that implement the SoftDeletes trait."
            );
        }

        if ($this->isBulk() && count($this->selected) > 0) {
            $this->model::withTrashed()
                 ->whereIn('id', $this->selected)
                 ->restore();
        }
    }
}
