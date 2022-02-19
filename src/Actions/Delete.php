<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
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
        return __(Str::ucfirst(Str::singular($this->entity) . ' deleted.'));
    }

    /**
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        $this->setRedirect(true);

        $this->entry->delete();
    }
}
