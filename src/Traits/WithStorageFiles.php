<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait WithStorageFiles
{
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

    protected function removeFile($file)
    {
        $disk = $this->getStorageDisk();
        $filename = $this->combinePath($this->getStoragePath(), $file);

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
        $id = $this->entry->getKey();

        return "{$this->entity}/{$id}/{$this->name}";
    }
}
