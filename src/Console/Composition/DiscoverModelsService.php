<?php

declare(strict_types=1);

namespace Flatpack\Console\Composition;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;

final readonly class DiscoverModelsService
{
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    /**
     * @return list<class-string<Model>>
     */
    public function discover(): array
    {
        $appPath = $this->app->basePath('app');
        if (! $this->files->isDirectory($appPath)) {
            return [];
        }

        $namespace = rtrim($this->app->getNamespace(), '\\');
        $candidates = [];

        foreach ($this->files->allFiles($appPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace([$appPath . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            $class = $namespace . '\\' . ltrim($relativeClass, '\\');

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            /** @var class-string<Model> $class */
            $candidates[] = $class;
        }

        $candidates = array_values(array_unique($candidates));
        sort($candidates);

        return $candidates;
    }
}
