<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

use Flatpack\Widgets\Data\Table\TableColumnData;
use Flatpack\Widgets\Data\Table\TablePaginationData;
use Flatpack\Widgets\Data\Table\TableSortingData;

final readonly class TableWidgetData implements WidgetPayload
{
    /**
     * @param  list<TableColumnData>  $columns
     * @param  list<array<string, int|float|string|bool|null>>  $rows
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public TableSortingData $sorting = new TableSortingData(),
        public TablePaginationData $pagination = new TablePaginationData(),
        public string $search = '',
    ) {}

    /**
     * @return array{
     *     columns: list<array<string, mixed>>,
     *     rows: list<array<string, int|float|string|bool|null>>,
     *     sorting: array{sort_by: string|null, sort_direction: string|null},
     *     pagination: array<string, mixed>,
     *     search: string
     * }
     */
    public function toArray(): array
    {
        return [
            'columns' => array_map(
                static fn (TableColumnData $c): array => $c->toArray(),
                $this->columns,
            ),
            'rows' => $this->rows,
            'sorting' => $this->sorting->toArray(),
            'pagination' => $this->pagination->toArray(),
            'search' => $this->search,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
