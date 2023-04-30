<?php

namespace Flatpack\Http\Middleware;

use Flatpack\Exceptions\EntityNotFoundException;
use Flatpack\Facades\Flatpack;
use Illuminate\Http\Request;

class FlatpackMiddleware
{
    /**
     * Flatpack template composition files options.
     */
    protected $compositions = [];

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
        abort_if(! $this->authorize($request), 401, 'Unauthorized.');

        // Load the flatpack template composition files.
        $this->compositions = Flatpack::loadComposition()->getComposition();

        // Validate the current request.
        $this->validate($request);

        // Append flatpack mappings to the request.
        $request->flatpack = [
            'entity' => $this->entity,
            'model' => $this->modelClass,
            'compositions' => $this->compositions,
            'options' => $this->getOptions($this->compositions),
        ];

        return $next($request);
    }

    /**
     * Authorize Http Request for the current user.
     * On local env, all users are allowed.
     *
     * @return bool
     */
    protected function authorize(Request $request): bool
    {
        return app()->environment('local') || $request->user()->isFlatpackAdmin();
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

        if (in_array($route->getAction('as'), [
            'flatpack.home',
            'flatpack.api.suggestions',
        ])) {
            return true;
        }

        $this->entity = $route->parameter('entity');

        $this->modelClass = Flatpack::guessModelClass($this->entity);

        $modelName = Flatpack::modelName($this->entity);

        $allowedValues = array_values(array_keys($this->compositions));

        if (! in_array($this->entity, $allowedValues)) {
            throw new EntityNotFoundException("Entity '{$this->entity}' not found.", $this->entity, $modelName);
        }

        return true;
    }

    /**
     * Get Flatpack entities options from compositions array.
     *
     * @param array $compositions
     * @return array
     */
    private function getOptions($compositions)
    {
        $options = collect($compositions)
            ->map(fn ($item) => collect($item)->mapWithKeys(
                fn ($item, $key) => [str_replace('.yaml', '', $key) => $item]
            ))
            ->mapWithKeys(fn ($item, $key) => [
                $key => [
                    'icon' => data_get($item, 'list.icon', 'folder'),
                    'model' => data_get($item, 'list.model', Flatpack::guessModelClass($key)),
                    'title' => data_get($item, 'list.title', Flatpack::modelName($key)),
                    'url' => route('flatpack.list', ['entity' => Flatpack::entityName($key)]),
                    'order' => data_get($item, 'list.order', 99),
                ],
            ])
            ->sortBy('order')
            ->toArray();

        return $options;
    }
}
