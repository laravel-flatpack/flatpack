<?php

namespace Flatpack\Actions;

use Flatpack\Contracts\FlatpackAction as FlatpackActionContract;
use Flatpack\Traits\WithStorageFiles;

class Upload extends FlatpackAction implements FlatpackActionContract
{
    use WithStorageFiles;

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
     * @return array
     */
    public function handle()
    {
        if (count($this->files)) {
            return $this->uploadFiles();
        }

        return [];
    }
}
