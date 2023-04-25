<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Facades\Flatpack;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class SearchBox extends Component
{
    /**
     * Search query.
     *
     * @var string
     */
    public $search = "";

    /**
     * Search results.
     *
     * @var array
     */
    public $results = [];

    /**
     * Show dropdown.
     *
     * @var bool
     */
    public $showDropdown = false;

    /**
     * Selected result index.
     *
     * @var int|null
     */
    public $selectedResult = null;

    /**
     * Highlight Index.
     *
     * @var int
     */
    public $highlightIndex = -1;

    public function mount()
    {
        $this->reset();
    }

    public function reset(...$properties)
    {
        $this->results = [];
        $this->highlightIndex = -1;
        $this->search = '';
        $this->selectedResult = null;
        $this->showDropdown = true;
    }

    public function hideDropdown()
    {
        $this->showDropdown = false;
    }

    public function incrementHighlight()
    {
        if ($this->highlightIndex === count($this->results) - 1) {
            $this->highlightIndex = 0;

            return;
        }

        $this->highlightIndex++;
    }

    public function decrementHighlight()
    {
        if ($this->highlightIndex === 0) {
            $this->highlightIndex = count($this->results) - 1;

            return;
        }

        $this->highlightIndex--;
    }

    /**
     * Update search results.
     *
     * @return void
     */
    public function updatedSearch()
    {
        if (Str::length($this->search) > 1) {
            $searchEntities = $this->globalSearchEntities();

            $this->results = $this->queryBuilder()
                ->orderBy('display')
                ->limit(10)
                ->get()
                ->map(function ($result) use ($searchEntities) {
                    $result = toArray($result);
                    $entity = data_get($result, 'entity');
                    $result['icon'] = data_get($searchEntities, "{$entity}.icon");

                    return $result;
                })
                ->toArray();

            $this->showDropdown = true;
        }
    }

    /**
     * Select the suggested result by key.
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function selectResult($key = null)
    {
        $id = ! is_null($key) ? $key : $this->highlightIndex;

        $selected = $this->results[$id] ?? null;

        if ($selected) {
            $this->showDropdown = true;
            $this->search = $selected['display'] ?? '';
            $this->selectedResult = $id;
        }

        if (isset($selected['entity']) && isset($selected['id'])) {
            $this->emit('redirect', flatpackUrl("/{$selected['entity']}/{$selected['id']}"));
        }
    }

    /**
     * Render search box.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('flatpack::components.search-box')
            ->with('searchEntities', $this->globalSearchEntities());
    }

    /**
     * Setup global search query builder.
     *
     * @return Builder
     */
    private function queryBuilder()
    {
        $entities = $this->globalSearchEntities();

        $queries = [];

        foreach ($entities as $entityName => $entity) {
            $query = DB::table($entity['table'])
                ->select([
                    $entity['column'] . ' AS display',
                    $entity['primaryKey'] . ' AS id',
                ])
                ->selectRaw("'{$entityName}' AS `entity`");

            $query
                ->whereNotNull($entity['column'])
                ->where($entity['column'], 'like', "%{$this->search}%");

            $queries[] = $query;
        }

        // Reduce all queries into a single one.

        $queryBuilder = array_reduce($queries, function ($previous, $query) {
            return is_null($previous) ? $query : $previous->union($query);
        }, null);

        return $queryBuilder;
    }

    /**
     * Get an array of entities and their search options.
     *
     * @return array
     */
    private function globalSearchEntities()
    {
        $composition = Arr::except(Flatpack::loadComposition()->getComposition(), '__global__');

        // Get searchable columns

        $searchable = Arr::map(
            $composition,
            fn ($value, $key) => array_keys(
                Arr::whereNotNull(
                    Arr::map(
                        Arr::get(Arr::get($value, 'list.yaml'), 'columns'),
                        fn ($entry, $key) => Arr::get($entry, 'searchable')
                    )
                )
            )
        );

        return Arr::whereNotNull(
            Arr::map($searchable, function ($values, $entity) use ($composition) {
                if (count($values) === 0) {
                    return null;
                }

                $icon = isset($composition[$entity]['list.yaml']) ?
                    data_get($composition[$entity]['list.yaml'], 'icon') :
                    null;

                $model = Flatpack::guessModelClass($entity);

                return [
                    'icon' => $icon,
                    'model' => $model,
                    'table' => (new $model())->getTable(),
                    'primaryKey' => (new $model())->getKeyName(),
                    'column' => Arr::first($values),
                ];
            })
        );
    }
}
