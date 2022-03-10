<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
use Faustoq\Flatpack\Traits\WithStorageFiles;

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
        if (! count($this->files)) {
            return;
        }

        return $this->uploadFiles();
    }
}
