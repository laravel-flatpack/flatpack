<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait WithStorageFiles
{
    /**
     * Files to upload.
     *
     * @var array
     */
    public $files;

    /**
     * Combine path and filename.
     *
     * @param  string  $path
     * @param  string  $filename
     * @return string
     */
    protected function combinePath($path, $file)
    {
        return Str::finish($path, '/') . ltrim($file, '/');
    }

    /**
     * Get the storage disk.
     *
     * @return string
     */
    protected function getStorageDisk()
    {
        return config('flatpack.storage.disk', 'public');
    }

    /**
     * Get the storage path for the entity.
     *
     * @return string
     */
    protected function getStoragePath()
    {
        return $this->combinePath(
            config('flatpack.storage.path', 'uploads'),
            $this->getEntityPath()
        );
    }

    protected function getFileName($url)
    {
        return last(explode('/', $url));
    }

    protected function removeFile($file)
    {
        $disk = $this->getStorageDisk();
        $filename = $this->combinePath($this->getStoragePath(), $this->getFileName($file));

        $exists = Storage::disk($disk)->exists($filename);

        if ($exists) {
            Storage::disk($disk)->delete($filename);
        }
    }

    /**
     * Clean up old files.
     *
     * @return bool
     */
    protected function cleanUp()
    {
        $disk = $this->getStorageDisk();
        $directory = $this->getStoragePath();

        return Storage::disk($disk)->deleteDirectory($directory);
    }

    /**
     * Get the path based on current entity.
     *
     * @return string
     */
    private function getEntityPath()
    {
        return "{$this->entity}/" . $this->entry->getKey();
    }

    /**
     * Return only the files that are not uploaded yet.
     *
     * @param  array  $files
     * @return array
     */
    protected function onlyFilesToUpload($files)
    {
        return collect($files)->filter(fn ($file) => $this->isFileToUpload($file))->toArray();
    }

    /**
     * Return only the files that are already uploaded.
     *
     * @param  string  $file
     * @return bool
     */
    protected function onlyFilesAlreadyUploaded($files)
    {
        return collect($files)->filter(fn ($file) => ! $this->isFileToUpload($file))->toArray();
    }

    /**
     * Check if the file is to be uploaded.
     *
     * @param  mixed  $file
     * @return bool
     */
    private function isFileToUpload($file)
    {
        return $file instanceof \Livewire\TemporaryUploadedFile;
    }

    /**
     * Upload action files and return the final urls.
     *
     * @return array
     */
    protected function uploadFiles()
    {
        $filesToUpload = $this->onlyFilesToUpload($this->files);

        $uploaded = $this->onlyFilesAlreadyUploaded($this->files);

        foreach ($filesToUpload as $file) {
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
    protected function uploadFile($file)
    {
        $disk = $this->getStorageDisk();

        $path = Storage::disk($disk)->putFile(
            $this->getStoragePath(),
            $file
        );

        return Storage::disk($disk)->url($path);
    }
}
