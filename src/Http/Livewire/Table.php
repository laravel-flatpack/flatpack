<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class Table extends DataTableComponent
{
    public bool $dumpFilters = false;

    public bool $columnSelect = true;

    public string $defaultSortColumn = 'id';

    public string $defaultSortDirection = 'DESC';

    public bool $reorderEnabled = false;

    public array $bulkActions = [
        'delete' => 'Delete',
    ];

    protected string $tableName = '';

    /**
     * Component props.
     */
    public $model;
    public $entity;
    public $composition = [];

    public function columns(): array
    {
        $columns = [];

        foreach ($this->composition['list'] ?? [] as $attribute => $options) {
            $label = $options['label'] ?? $attribute;
            $column = Column::make($label, $attribute);

            if (isset($options['sortable']) && $options['sortable']) {
                $column->sortable();
            }

            if (isset($options['searchable']) && $options['searchable']) {
                $column->searchable();
            }

            $columns[] = $column;
        }

        return $columns;
    }

    public function filters(): array
    {
        return [];
    }

    public function query(): Builder
    {
        return $this->model::query();
    }

    public function getTableRowUrl($row): string
    {
        return route('flatpack.form', [
            'entity' => $this->entity,
            'id' => $row->id,
        ]);
    }

    public function render()
    {
        return view('flatpack::components.table')
            ->with([
                'columns' => $this->columns(),
                'rowView' => $this->rowView(),
                'filtersView' => $this->filtersView(),
                'customFilters' => $this->filters(),
                'rows' => $this->getRowsProperty(),
                'modalsView' => $this->modalsView(),
            ]);
    }
}
