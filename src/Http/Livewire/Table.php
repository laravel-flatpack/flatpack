<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithActions;
use Flatpack\Traits\WithColumns;
use Flatpack\Traits\WithComposition;
use Flatpack\Traits\WithFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

class Table extends DataTableComponent
{
    use WithActions;
    use WithComposition;
    use WithColumns;
    use WithFilters;

    /**
     * Model class name.
     *
     * @var string
     */
    public $model;

    /**
     * Entity name.
     *
     * @var string
     */
    public $entity;

    /**
     * Layout composition.
     *
     * @var array
     */
    public $composition = [];

    /**
     * Runs once, immediately after the component is instantiated.
     *
     * @param string $model
     * @param string $entity
     * @param array $composition
     * @return void
     */
    public function mount($model, $entity, $composition): void
    {
        $this->model = $model;
        $this->entity = $entity;
        $this->composition = $composition;

        $this->{$this->tableName} = [
            'sorts' => $this->{$this->tableName}['sorts'] ?? [],
            'filters' => $this->{$this->tableName}['filters'] ?? [],
            'columns' => $this->{$this->tableName}['columns'] ?? [],
        ];

        $this->setFilterOptions();
        $this->setFilterDefaults();
    }

    /**
     * Configure Table component.
     *
     * @return void
     */
    public function configure(): void
    {
        $primaryKey = (new $this->model())->getKeyName();

        $this->setupComponents()
            ->setQueryStringDisabled()
            ->setPrimaryKey($primaryKey)
            ->setDefaultSort($primaryKey, 'desc')
            ->setTableRowUrl(fn ($row) => route('flatpack.form', [
                'entity' => $this->entity,
                'id' => $row->id,
            ]));

        $this->setBuilder($this->builder());
    }

    /**
     * Query Builder
     *
     * @return Builder
     */
    public function builder(): Builder
    {
        return $this->getModel()::query()->with($this->getRelationships());
    }

    /**
     * Setup base query builder with filters and sorting.
     *
     * @return Builder
     */
    protected function baseQuery(): Builder
    {
        $this->setBuilder($this->joinRelations());
        $this->setBuilder($this->applySearch());
        $this->setBuilder($this->applyFilters());

        return $this->applySorting();
    }

    /**
     * Execute bulk action.
     *
     * @param string $action
     * @return void
     */
    public function bulkAction($action)
    {
        $selected = $this->getSelected();
        $action = $this->getAction($action)->setSelectedKeys($selected);

        $action->run();

        $this->selected = [];
    }

    /**
     * Setup list components.
     *
     * @return self
     */
    private function setupComponents(): self
    {
        foreach ($this->composition['bulk'] ?? [] as $key => $options) {
            if (isset($options['action'])) {
                $this->bulkActions[$options['action']] = $options['label'] ?? $key;
            }
        }

        return $this;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->setupColumnSelect();
        $this->setupPagination();
        $this->setupSecondaryHeader();
        $this->setupFooter();
        $this->setupReordering();

        return view('flatpack::components.table')
            ->with([
                'toolbar' => $this->getComposition('toolbar'),
                'columns' => $this->getColumns(),
                'rows' => $this->getRows(),
                'customView' => $this->customView(),
            ]);
    }
}
