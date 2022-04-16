<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;

class Delete extends FlatpackAction implements FlatpackActionContract
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
            __(':count :entity deleted.', [
                'count' => count($this->selected),
                'entity' => $this->entityName(true),
            ]) :
            __(':entity deleted.', [
                'entity' => $this->entityName()
            ]);
    }

    /**
     * Get confirmation message.
     *
     * @return string
     */
    public function getConfirmationMessage()
    {
        return $this->isBulk() ?
            __('Are you sure you want to delete the selected :entity?', [
                'entity' => $this->entityName(true)
            ]) :
            __('Are you sure you want to delete this :entity?', [
                'entity' => $this->entityName()
            ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isMultiple && count($this->selected) > 0) {
            $this->model::withTrashed()
                 ->whereIn('id', $this->selected)
                 ->delete();
        } else {
            $this->entry->delete();
            $this->setRedirect(true);
        }
    }
}
