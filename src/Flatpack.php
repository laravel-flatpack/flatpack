<?php

namespace Flatpack;

use Flatpack\Exceptions\ConfigurationException;
use Flatpack\Exceptions\EntityNotFoundException;
use Flatpack\Exceptions\ModelNotFoundException;
use Flatpack\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Flatpack
{
    public const VERSION = "1.0";

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

        return $files
            ->filter(fn ($file) => ! empty($file->getRelativePath()))
            ->filter(fn ($file) => $file->getExtension() === 'yaml');
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
            $config[$entity][$file->getFilename()] = Yaml::parseFile($file->getPathname());
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
        $name = collect(explode('\\', $name))->last();

        return Str::of($name)->plural()->lower()->toString();
    }

    /**
     * Get the model class name by the entity name.
     *
     * @param  string $name
     * @return string
     */
    public function modelName($name = ''): string
    {
        return Str::of($name)->singular()->studly()->toString();
    }

    public function getModelsDirectory()
    {
        return config('flatpack.models');
    }

    /**
     * Get the model class by the entity name.
     *
     * @param  string $name
     * @throws ModelNotFoundException
     * @return string
     */
    public function guessModelClass($name = ''): string
    {
        $modelClass = Str::finish($this->getModelsDirectory(), '\\') . $this->modelName($name);

        if (! class_exists($modelClass)) {
            throw new ModelNotFoundException("Model '{$modelClass}' not found.", $name, $this->modelName($name));
        }

        return $modelClass;
    }

    /**
     * Get the directory path of the Flatpack templates.
     *
     * @throws ConfigurationException
     * @return string
     */
    public function getDirectory(): string
    {
        return (app()->environment('testing')) ?
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
