<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
use Illuminate\Support\Facades\Storage;

class Upload extends FlatpackAction implements FlatpackActionContract
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
        if (! $this->file) {
            return;
        }

        $disk = config('flatpack.storage.disk', 'public');

        $path = Storage::disk($disk)->putFile(
            config('flatpack.storage.path', 'uploads'),
            $this->file
        );

        return Storage::disk($disk)->url($path);
    }
}
