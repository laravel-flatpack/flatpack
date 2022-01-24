<?php

namespace Faustoq\Flatpack;

use Faustoq\Flatpack\Exceptions\ConfigurationException;
use Faustoq\Flatpack\Exceptions\EntityNotFoundException;
use Faustoq\Flatpack\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Flatpack
{
    const GLOBAL_OPTIONS_KEY = '_flatpack_global';

    /**
     * Get the model class name by the entity name.
     *
     * @param  string $name
     * @return string
     */
    public static function modelName($name = ''): string
    {
        return Str::studly(Str::singular($name));
    }

    /**
     * Get the model class by the entity name.
     *
     * @param  string $name
     * @return string
     */
    public static function guessModelClass($name = ''): string
    {
        $modelClass = 'App\\' . self::modelName($name);

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        if (is_dir(app_path('Models/'))) {
            $modelClass = 'App\\Models\\' . self::modelName($name);

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return self::modelName($name);
    }

    /**
     * Get the entity name by the model class name.
     *
     * @param  string $name
     * @return string
     */
    public static function entityName($name = ''): string
    {
        return Str::lower(Str::plural($name));
    }

    /**
     * Get the directory path of the Flatpack templates.
     *
     * @return string
     */
    public static function getDirectory(): string
    {
        $path = base_path(config('flatpack.directory', 'flatpack'));
        if (!File::isDirectory($path)) {
            throw new ConfigurationException('Flatpack directory not found.');
        }
        return $path;
    }

    /**
     * Get the template files for the given entity.
     *
     * @param  string $entity
     * @return string
     */
    public static function getTemplates($entity)
    {
        $templates = Cache::get(config('flatpack.cache.key', 'flatpack.templates'), []);

        if ($templates === [] || false === config('flatpack.cache.enabled', true)) {
            $templates = self::loadYamlConfigFiles(self::getDirectory());
        }

        if (!isset($templates[$entity])) {
            throw new EntityNotFoundException("Entity '{$entity}' not found.", $entity, self::modelName($entity));
        }

        return $templates[$entity];
    }

    /**
     * Get template file composition structure.
     *
     * @param  string $entity
     * @param  string $template
     * @return string
     */
    public static function getTemplateComposition($entity, $template = 'list.yaml')
    {
        $templates = self::getTemplates($entity);

        if (!isset($templates[$template])) {
            throw new TemplateNotFoundException("Template '{$template}' not found.", $entity, self::modelName($entity));
        }

        return $templates[$template];
    }

    /**
     * Load all YAML config files in the given path.
     *
     * @return array
     */
    public static function loadYamlConfigFiles($path)
    {
        $files = collect(File::allFiles($path));
        $config = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'yaml') {
                continue;
            }

            $path = $file->getPathname();
            $entity = $file->getRelativePath();
            $file = $file->getFilename();

            $view = Yaml::parseFile($path);

            $key = empty($entity) ? self::GLOBAL_OPTIONS_KEY : $entity;

            $config[$key][$file] = $view;
        }

        // TODO: Save to Cache...


        return $config;
    }
}
