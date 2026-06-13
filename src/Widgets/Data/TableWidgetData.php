<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

final readonly class TableWidgetData implements WidgetPayload
{
    /**
     * @param  list<array<string, int|float|string|bool|null>>  $rows
     * @param  list<array<string, mixed>>|array<string, array<string, mixed>>|null  $columns
     */
    public function __construct(
        public array $rows,
        public ?array $columns = null,
    ) {}

    /**
     * @return array{rows: list<array<string, int|float|string|bool|null>>, columns?: list<array<string, mixed>>|array<string, array<string, mixed>>}
     */
    public function toArray(): array
    {
        if ($this->columns === null) {
            return ['rows' => $this->rows];
        }

        return [
            'rows' => $this->rows,
            'columns' => $this->columns,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
