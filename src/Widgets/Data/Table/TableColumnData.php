<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data\Table;

use Flatpack\Widgets\Data\WidgetPayload;

final readonly class TableColumnData implements WidgetPayload
{
    public function __construct(
        public string $id,
        public string $label,
        public string $type = 'text',
        public bool $sortable = false,
        public bool $searchable = false,
    ) {}

    /**
     * @return array{id: string, label: string, type: string, sortable: bool, searchable: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
