<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;
use Illuminate\Support\Str;

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
        if ($this->isMultiple) {
            return __(':count :entity restored.', [
                'count' => count($this->selected),
                'entity' => Str::lower(Str::plural($this->entity)),
            ]);
        }

        return __(':entity restored.', [
            'entity' => Str::ucfirst(Str::singular($this->entity)),
        ]);
    }

    public function getBulkConfirmationMessage()
    {
        return __('Are you sure you want to restore the selected :entity?', [ 'entity' => Str::plural($this->entity) ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->selected && is_array($this->selected) && count($this->selected) > 0) {
            $this->model::withTrashed()->whereIn('id', $this->selected)->restore();
        }
    }
}
