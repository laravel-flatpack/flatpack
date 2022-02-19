<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
use Faustoq\Flatpack\Traits\WithRelationships;
use Illuminate\Support\Str;

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
        return __(Str::ucfirst(Str::singular($this->entity) . ' saved.'));
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
     * Sync fields to model relationships.
     *
     * @return void
     */
    private function syncFieldsToRelations()
    {
        foreach ($this->fields as $key => $options) {
            if ((isset($options['disabled']) && $options['disabled']) ||
                (isset($options['readonly']) && $options['readonly']) ||
                $this->isRelationship($key) === false) {
                continue;
            }

            $this->syncRelationship($key);
        }

        $this->entry->save();
        $this->entry->refresh();
    }
}
