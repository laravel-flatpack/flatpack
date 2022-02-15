<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

class Table extends DataTableComponent
{
    public bool $dumpFilters = false;

    public bool $columnSelect = true;

    public bool $rememberColumnSelection = true;

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
                $column->selected();
            }

            $column->format($this->formatColumn($options));

            if ((isset($options['type']) && $options['type'] === 'image')) {
                $column->addClass('table-image-column');
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
                'searchableColumns' => $this->getSearchableColumns(),
                'rowView' => $this->rowView(),
                'filtersView' => $this->filtersView(),
                'customFilters' => $this->filters(),
                'rows' => $this->getRowsProperty(),
                'modalsView' => $this->modalsView(),
            ]);
    }

    /**
     * Format column.
     *
     * @param array $options
     * @return string
     */
    private function formatColumn($options)
    {
        $type = Arr::get($options, 'type', 'default');

        $format = Arr::get($options, 'format', 'Y-m-d H:i:s');

        $map = [
            'default' => function ($value) {
                return $value;
            },
            'datetime' => function ($value) use ($format) {
                return method_exists($value, 'format') ? $value->format($format) : $value;
            },
            'image' => function ($value) {
                return view('flatpack::includes.table.cells.image', [
                    'src' => $value,
                ]);
            },
            'boolean' => function ($value) {
                return view('flatpack::includes.table.cells.boolean', [
                    'boolean' => $value,
                ]);
            },
        ];

        return $map[$type] ?? $map['default'];
    }
}
