<?php

namespace Flatpack\Commands\Traits;

use Flatpack\Facades\Flatpack;

trait CreatesDirectory
{
    /**
     * Return the Flatpack directory name for the files.
     *
     * @return string
     */
    protected function flatpackDir($name)
    {
        return $this->laravel->basePath() . "/" .
            config('flatpack.directory', 'flatpack') . "/" .
            Flatpack::entityName($name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if ($this->hasOption('force') && $this->option('force') === true) {
            $this->files->delete($path);
        }

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }
}
