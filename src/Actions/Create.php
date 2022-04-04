<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;

class Create extends FlatpackAction implements FlatpackActionContract
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
     * Handle action.
     *
     * @return void
     */
    public function handle()
    {
        return redirect()->route("flatpack.form", [
            "entity" => $this->entity,
            "id" => "create",
        ]);
    }
}
