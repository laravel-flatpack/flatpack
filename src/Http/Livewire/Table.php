<?php

namespace Flatpack\Http\Livewire;

use Flatpack\Traits\WithActions;
use Flatpack\Traits\WithComposition;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

class Table extends DataTableComponent
{
    use WithActions;
    use WithComposition;

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
     * Query scope.
     *
     * @var null|string
     */
    public $scope = 'default';

    /**
     * Configure Table component.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->setComponents()
            ->setPrimaryKey((new $this->model())->getKeyName())
            ->setTableRowUrl(function ($row) {
                return route('flatpack.form', [
                    'entity' => $this->entity,
                    'id' => $row->id,
                ]);
            });

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

        if ($this->currentlyReorderingIsEnabled()) {
            $this->setBuilder(
                $this->getBuilder()->orderBy(
                    $this->getDefaultReorderColumn(),
                    $this->getDefaultReorderDirection()
                )
            );

            return $this->getBuilder();
        }

        return $this->applySorting();
    }

    public function isTrashed($row): bool
    {
        return method_exists($row, 'trashed') && $row->trashed();
    }

    /**
     * Execute action.
     *
     * @param string $action
     * @param array $options
     * @return void
     */
    public function action($action, $options = []): void
    {
        $action = $this->getAction($action);
        $action->run();
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
     * Table columns definition.
     *
     * @return array
    */
    public function columns(): array
    {
        $columns = [];

        foreach ($this->composition['columns'] ?? [] as $attribute => $options) {
            $label = $options['label'] ?? $attribute;
            $column = Column::make($label, $attribute);

            if (isset($options['sortable']) && $options['sortable']) {
                $column->sortable();
            }

            if (isset($options['searchable']) && $options['searchable']) {
                $column->searchable();
            }

            if (! (isset($options['invisible']) && $options['invisible'])) {
                $column->isSelectable();
            }

            if (isset($options['width'])) {
                $column->setWidth($options['width']);
            }

            $column->format($this->formatColumn($options));

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Format column.
     *
     * @param array $options
     * @return string
     */
    private function formatColumn($options)
    {
        $type = data_get($options, 'type', 'default');
        $format = data_get($options, 'format', 'Y-m-d H:i:s');

        $map = [
            'default' => fn ($value) => $value,
            'datetime' => function ($value) use ($format) {
                return method_exists($value, 'format') ? $value->format($format) : $value;
            },
            'image' => fn ($value) => view('flatpack::includes.table.cells.image', [
                    'src' => $value,
            ]),
            'boolean' => fn ($value) => view('flatpack::includes.table.cells.boolean', [
                'boolean' => $value,
            ]),
        ];

        return $map[$type] ?? $map['default'];
    }

    /**
     * Setup list components.
     *
     * @return self
     */
    private function setComponents(): self
    {
        // Bulk actions
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
                'scopes' => $this->getComposition('scopes'),
                'toolbar' => $this->getComposition('toolbar'),
                'columns' => $this->getColumns(),
                'rows' => $this->getRows(),
                'customView' => $this->customView(),
            ]);
    }
}
