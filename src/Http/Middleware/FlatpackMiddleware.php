<?php

namespace Flatpack\Http\Middleware;

use Flatpack\Exceptions\EntityNotFoundException;
use Flatpack\Exceptions\ModelNotFoundException;
use Flatpack\Facades\Flatpack;
use Illuminate\Http\Request;

class FlatpackMiddleware
{
    /**
     * Flatpack template composition files options.
     */
    protected $options = [];

    /**
     * Entity name.
     */
    protected $entity;

    /**
     * Model class name.
     */
    protected $modelClass;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $this->authorize($request);

        // Load and cache the flatpack template composition files.
        $this->options = Flatpack::loadComposition()->getComposition();

        // Validate the current request.
        $this->validate($request);

        // Append flatpack mappings to the request.
        $request->flatpack = [
            'entity' => $this->entity,
            'model' => $this->modelClass,
            'compositions' => $this->options,
        ];

        return $next($request);
    }

    protected function authorize(Request $request)
    {
        $user = $request->user();

        abort_if(! $user->isFlatpackAdmin(), 401, 'Unauthorized.');

        return true;
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
            return true;
        }

        $allowedValues = array_values(
            collect(array_keys($this->options))
                ->filter(fn ($key) => $key !== '__global__')
                ->toArray()
        );

        $this->entity = $route->parameter('entity');

        $modelName = Flatpack::modelName($this->entity);

        $this->modelClass = Flatpack::guessModelClass($this->entity);

        if (! class_exists($this->modelClass)) {
            throw new ModelNotFoundException("Model '{$this->modelClass}' not found.", $this->entity, $modelName);
        }

        if (! in_array($this->entity, $allowedValues)) {
            throw new EntityNotFoundException("Entity '{$this->entity}' not found.", $this->entity, $modelName);
        }

        return true;
    }
}
