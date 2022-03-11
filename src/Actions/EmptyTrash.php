<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
use Illuminate\Support\Str;

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
            'entity' => Str::ucfirst(Str::plural($this->entity)),
        ]);
    }

    public function getConfirmationMessage()
    {
        return __('Are you sure you want to permanently delete these :entity?', [
            'entity' => Str::plural($this->entity)
        ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        $this->model::onlyTrashed()->forceDelete();
        $this->setRedirect(true);
    }
}
