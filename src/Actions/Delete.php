<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackActionContract;
use Illuminate\Support\Str;

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
        if ($this->isMultiple) {
            return __(':count :entity deleted.', [
                'count' => count($this->selected),
                'entity' => Str::lower(Str::plural($this->entity)),
            ]);
        }

        return __(':entity deleted.', [
            'entity' => Str::ucfirst(Str::singular($this->entity)),
        ]);
    }

    public function getConfirmationMessage()
    {
        return __('Are you sure you want to delete this :entity?', [ 'entity' => Str::singular($this->entity) ]);
    }

    public function getBulkConfirmationMessage()
    {
        return __('Are you sure you want to delete the selected :entity?', [ 'entity' => Str::plural($this->entity) ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->isMultiple) {
            $this->entry->delete();
            $this->setRedirect(true);
        } elseif ($this->selected && is_array($this->selected) && count($this->selected) > 0) {
            $this->model::whereIn('id', $this->selected)->delete();
        }
    }
}
