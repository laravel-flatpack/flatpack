<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;

class EmptyTrash extends FlatpackAction implements FlatpackActionContract
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
        return __(':entity permanently deleted.', [
            'entity' => $this->entityName(true),
        ]);
    }

    /**
     * Get confirmation message.
     *
     * @return string
     */
    public function getConfirmationMessage()
    {
        return __('Are you sure you want to permanently delete these :entity?', [
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
        $this->model::onlyTrashed()
             ->forceDelete();

        $this->setRedirect(true);
    }
}
