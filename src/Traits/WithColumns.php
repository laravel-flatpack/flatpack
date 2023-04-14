<?php

namespace Flatpack\Traits;

use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn;

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
            $columns[] = $this->createColumn($attribute, $options);
        }

        return $columns;
    }

    /**
     * Create column.
     *
     * @param string $key
     * @param array $options
     * @return string
     */
    private function createColumn($key, $options)
    {
        $type = data_get($options, 'type', 'default');
        $label = data_get($options, 'label', $key);
        $format = data_get($options, 'format', 'Y-m-d H:i:s');
        $sortable = data_get($options, 'sortable', false);
        $searchable = data_get($options, 'searchable', false);
        $invisible = data_get($options, 'invisible', false);

        $map = [
            'default' => Column::make($label, $key),
            'image' => ImageColumn::make($label, $key),
            'boolean' => BooleanColumn::make($label, $key),
        ];

        $column = $map[$type] ?? $map['default'];

        if ($sortable) {
            $column->sortable();
        }

        if ($searchable) {
            $column->searchable();
        }

        if ($invisible) {
            $column->deselected();
        }

        if ($format) {
            $column->format(fn ($value) => Str::of(
                is_object($value) && method_exists($value, 'format') ?
                    $value->format($format) :
                    $value
            )->toString());
        }

        return $column;
    }
}
