<?php

namespace Faustoq\Flatpack\Actions;

use Faustoq\Flatpack\Contracts\FlatpackActionContract;
use Faustoq\Flatpack\Traits\WithStorageFiles;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload action files and return the final urls.
     *
     * @return array
     */
    private function uploadFiles()
    {
        $this->cleanUp();

        $uploaded = [];

        foreach ($this->files as $file) {
            $uploaded[] = $this->uploadFile($file);
        }

        return $uploaded;
    }

    /**
     * Upload a file to storage.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    private function uploadFile($file)
    {
        $disk = $this->getStorageDisk();

        $path = Storage::disk($disk)->putFile(
            $this->getStoragePath(),
            $file
        );

        return Storage::disk($disk)->url($path);
    }
}
