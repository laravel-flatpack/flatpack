<?php

namespace Faustoq\Flatpack\Http\Middleware;

use Faustoq\Flatpack\Flatpack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class FlatpackMiddleware
{
    /**
     * Flatpack global options.
     */
    protected $globalOptionsKey = '_flatpack_global';

    /**
     * Flatpack template composition files options.
     */
    protected $options = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // Load and cache the flatpack template composition files.
        $this->options = $this->loadFlatpack();

        // Validate the current request.
        $this->validate($request);

        return $next($request);
    }

    /**
     * Load Flatpack template composition files.
     *
     * @return array
     */
    private function loadFlatpack()
    {
        $path = app()->basePath(config('flatpack.directory', 'flatpack'));
        $config = $this->loadYamlConfigFiles($path);

        return $config;
    }

    /**
     * Load all YAML config files in the given path.
     *
     * @return array
     */
    private function loadYamlConfigFiles($path)
    {
        $files = collect(File::allFiles($path));
        $config = [];
        foreach ($files as $file) {
            $path = $file->getPathname();
            $entity = $file->getRelativePath();
            $file = $file->getFilename();

            $view = Yaml::parseFile($path);
            $key = empty($entity) ? $this->globalOptionsKey : $entity;

            $config[$key][$file] = $view;
        }

        return $config;
    }

    /**
     * Validate route parameters.
     *
     * @param \Illuminate\Http\Request  $request
     * @return void
     */
    private function validate(Request $request)
    {
        $route = $request->route();

        if (! $route instanceof \Illuminate\Routing\Route) {
            abort(404, 'Route not found.');
        }

        if ($route->parameters() === [] && $route->getAction('as') === 'flatpack.home') {
            return;
        }

        $options = $this->options;

        $allowedValues = array_values(
            collect(array_keys($options))
                ->filter(fn ($key) => $key !== $this->globalOptionsKey)
                ->toArray()
        );

        $entity = $route->parameter('entity');
        if (! in_array($entity, $allowedValues)) {
            abort(404, "Entity '{$entity}' not found.");
        }

        $modelClass = Flatpack::guessModelClass($entity);
        if (! class_exists($modelClass)) {
            abort(404, "Model class '{$modelClass}' not found.");
        }

        $request->model = $modelClass;
    }
}
