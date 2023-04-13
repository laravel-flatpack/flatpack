<?php

namespace Flatpack\Traits;

use Flatpack\Http\Livewire\Column;

trait WithColumns
{
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

            if (isset($options['invisible']) && $options['invisible']) {
                $column->deselected();
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
}
