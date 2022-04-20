<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;
use Illuminate\Database\Eloquent\Builder;

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
        return $this->isBulk() ?
            __('Are you sure you want to delete the selected :entity?', [
                'entity' => $this->entityName(true),
            ]) :
            __('Are you sure you want to delete this :entity?', [
                'entity' => $this->entityName(),
            ]);
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isBulk() && count($this->selected) > 0) {
            $this->query()->whereIn('id', $this->selected)->delete();
        } else {
            $this->entry->delete();
            $this->setRedirect(true);
        }
    }

    /**
     * Get query builder.
     *
     * @return Builder
     */
    private function query(): Builder
    {
        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model))) {
            return $this->model::withTrashed();
        }

        return $this->model::query();
    }
}
