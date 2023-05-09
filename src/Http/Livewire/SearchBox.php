<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Facades\Flatpack;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $searchEntities = $this->globalSearchEntities();
        $query = $this->queryBuilder();

        if (Str::length($this->search) > 1 && count($searchEntities) && ! is_null($query)) {
            $this->results = $query->orderBy('display')
                ->limit(10)
                ->get()
                ->map(function ($result) use ($searchEntities) {
                    $result = collect($result)->toArray();
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
        $id = ! empty($key) ? $key : $this->highlightIndex;

        $selected = $this->results[$id] ?? null;

        if ($selected) {
            $this->showDropdown = true;
            $this->search = $selected['display'] ?? '';
            $this->selectedResult = $id;
        }

        if (isset($selected['entity']) && isset($selected['id'])) {
            $this->emit('redirect', flatpackUrl("/{$selected['entity']}/{$selected['id']}"), 0);
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
                ->where($entity['column'], 'LIKE', "%{$this->search}%");

            if (Schema::hasColumn($entity['table'], 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $queries[] = $query;
        }

        // Reduce all queries into a single one.
        return array_reduce(
            $queries,
            fn ($previous, $query) => empty($previous) ? $query : $previous->union($query),
            null
        );
    }

    /**
     * Get an array of entities and their search options.
     *
     * @return array
     */
    private function globalSearchEntities()
    {
        $compositions = Flatpack::loadComposition()->getComposition();

        return collect($compositions)
            ->map(fn ($composition) => Arr::get($composition, 'list.yaml'))
            ->map(fn ($composition) => Arr::get($composition, 'columns'))
            ->map(
                fn ($columns) => collect($columns)
                    ->filter(
                        fn ($column) => (
                            Arr::has($column, 'searchable') &&
                            Arr::get($column, 'searchable') === true
                        )
                    )
                    ->keys()
            )
            ->filter(fn ($columns) => count($columns))
            ->map(function ($values, $entity) use ($compositions) {
                $model = Flatpack::guessModelClass($entity);

                return [
                    'icon' => data_get($compositions[$entity]['list.yaml'], 'icon'),
                    'model' => $model,
                    'table' => (new $model())->getTable(),
                    'primaryKey' => (new $model())->getKeyName(),
                    'column' => Arr::first($values),
                ];
            })
            ->toArray();
    }
}
