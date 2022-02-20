<?php

namespace Faustoq\Flatpack\Http\Livewire;

use Faustoq\Flatpack\Traits\WithActions;
use Faustoq\Flatpack\Traits\WithComposition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

class Table extends DataTableComponent
{
    use WithActions;
    use WithComposition;

    public bool $dumpFilters = false;

    public bool $columnSelect = true;

    public string $defaultSortColumn = 'id';

    public string $defaultSortDirection = 'DESC';

    public bool $reorderEnabled = false;

    public array $bulkActions = [];

    protected string $tableName = '';

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

    public function mount()
    {
        foreach ($this->composition['bulk'] ?? [] as $key => $options) {
            if (isset($options['action'])) {
                $this->bulkActions[$options['action']] = $options['label'] ?? $key;
            }
        }
    }

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

    public function action($action, $options = [])
    {
        $action = $this->getAction($action);
        $action->run();
    }

    public function bulkAction($action)
    {
        try {
            $action = $this->getAction($action)
                ->setSelectedKeys($this->selectedKeys());

            $action->run();

            $this->selected = [];
            $this->resetBulk();

            // Action success notification
            if (method_exists($action, 'getMessage') && $action->isSuccess()) {
                $this->notifySuccess($action->getMessage());
            } else {
                $this->notifySuccess('Done!');
            }
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
        }
    }

    public function render()
    {
        return view('flatpack::components.table')
            ->with([
                'toolbar' => $this->getComposition('toolbar'),
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

    private function notifySuccess($message)
    {
        $this->emit('notify', [
            "type" => "success",
            "message" => $message,
        ]);
    }

    private function notifyError($error, $errors = [])
    {
        return $this->emit('notify', [
            "type" => "error",
            "message" => $error,
            "errors" => $errors,
        ]);
    }
}
