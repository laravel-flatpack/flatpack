<?php

namespace Flatpack;

use Flatpack\Exceptions\ConfigurationException;
use Flatpack\Exceptions\EntityNotFoundException;
use Flatpack\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Flatpack
{
    public const VERSION = "1.0.7";

    /**
     * The configuration files path.
     *
     * @var string
     */
    protected $path;

    /**
     * The configuration files.
     *
     * @var \Symfony\Component\Finder\SplFileInfo[]
     */
    protected $files;

    /**
     * The configuration data.
     *
     * @var array
     */
    protected $composition;

    /**
     * Create a new Flatpack class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->composition = [];
        $this->path = $this->getDirectory();
    }

    /**
     * Load composition data and return Flatpack instance.
     *
     * @return self
     */
    public function loadComposition()
    {
        // TODO: cache the composition data.

        $this->files = $this->loadCompositionFiles();

        $composition = $this->parseCompositionFiles();

        $this->setComposition($composition);

        return $this;
    }

    /**
     * Set composition data.
     *
     * @return void
     */
    private function setComposition($value)
    {
        ksort($value);
        $this->composition = $value;
    }

    /**
     * Get composition data.
     *
     * @return array
     */
    public function getComposition()
    {
        return $this->composition;
    }

    /**
     * Load Flatpack composition Yaml files.
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    protected function loadCompositionFiles()
    {
        $files = collect(File::allFiles($this->path));

        $files = $files->filter(function ($file) {
            return $file->getExtension() === 'yaml';
        });

        return $files;
    }

    /**
     * Parse Flatpack composition Yaml files and return the data.
     *
     * @return array
     */
    protected function parseCompositionFiles()
    {
        $config = [];

        foreach ($this->files as $file) {
            $entity = $file->getRelativePath();

            $key = empty($entity) ? '__global__' : $entity;

            $config[$key][$file->getFilename()] = Yaml::parseFile($file->getPathname());
        }

        return $config;
    }

    /**
     * Get the entity name by the model class name.
     *
     * @param  string $name
     * @return string
     */
    public function entityName($name = ''): string
    {
        return Str::lower(Str::plural($name));
    }

    /**
     * Get the model class name by the entity name.
     *
     * @param  string $name
     * @return string
     */
    public function modelName($name = ''): string
    {
        return Str::studly(Str::singular($name));
    }

    /**
     * Get the model class by the entity name.
     *
     * @param  string $name
     * @return string
     */
    public function guessModelClass($name = ''): string
    {
        $modelClass = 'App\\' . $this->modelName($name);

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        if (is_dir(app_path('Models/'))) {
            $modelClass = 'App\\Models\\' . $this->modelName($name);

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return $this->modelName($name);
    }

    /**
     * Get the directory path of the Flatpack templates.
     *
     * @throws ConfigurationException
     * @return string
     */
    public function getDirectory(): string
    {
        return (config('app.env') === 'testing') ?
            (__DIR__ . '/../tests/__mocks__') :
            base_path(config('flatpack.directory', 'flatpack'));
    }

    /**
     * Get the template files for the given entity.
     *
     * @param  string $entity
     * @return array
     */
    private function getTemplates($entity)
    {
        if (! isset($this->composition[$entity])) {
            throw new EntityNotFoundException("Entity '{$entity}' not found.", $entity, $this->modelName($entity));
        }

        return $this->composition[$entity];
    }

    /**
     * Get template file composition structure.
     *
     * @param  string $entity
     * @param  string $template
     * @return array
     */
    public function getTemplateComposition($entity, $template = 'list.yaml')
    {
        $templates = $this->getTemplates($entity);

        if (! isset($templates[$template])) {
            throw new TemplateNotFoundException(
                "Template '{$template}' not found.",
                $entity,
                $this->modelName($entity)
            );
        }

        return $templates[$template];
    }
}
