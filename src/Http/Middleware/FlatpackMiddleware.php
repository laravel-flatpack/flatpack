<?php

namespace Faustoq\Flatpack\Http\Middleware;

use Faustoq\Flatpack\Exceptions\ConfigurationException;
use Faustoq\Flatpack\Exceptions\EntityNotFoundException;
use Faustoq\Flatpack\Exceptions\ModelNotFoundException;
use Faustoq\Flatpack\Flatpack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class FlatpackMiddleware
{
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
        $path = Flatpack::getDirectory();

        if (!File::isDirectory($path)) {
            throw new ConfigurationException('Flatpack directory not found.');
        }

        return Flatpack::loadYamlConfigFiles($path);
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

        $allowedValues = array_values(
            collect(array_keys($this->options))
                ->filter(fn ($key) => $key !== Flatpack::GLOBAL_OPTIONS_KEY)
                ->toArray()
        );

        $entity = $route->parameter('entity');
        $modelClass = Flatpack::guessModelClass($entity);

        if (! class_exists($modelClass)) {
            throw new ModelNotFoundException("Model '{$modelClass}' not found.", $entity, $modelClass);
        }

        if (! in_array($entity, $allowedValues)) {
            throw new EntityNotFoundException("Entity '{$entity}' not found.", $entity, $modelClass);
        }

        $request->model = $modelClass;
    }
}
