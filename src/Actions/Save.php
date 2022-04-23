<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;
use Flatpack\Traits\WithRelationships;

class Save extends FlatpackAction implements FlatpackActionContract
{
    use WithRelationships;

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
        return __(':entity saved.', [
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
        $this->entry->save();

        $this->syncFieldsToRelations();
    }

    /**
     * Save model relationships.
     *
     * @return void
     */
    protected function syncFieldsToRelations()
    {
        foreach ($this->fields as $key => $options) {
            $this->syncRelationship($key);
        }

        $this->entry->save();

        $this->entry->refresh();
    }
}
