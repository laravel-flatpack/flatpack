<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data\Table;

use Flatpack\Widgets\Data\WidgetPayload;

final readonly class TableSortingData implements WidgetPayload
{
    public function __construct(
        public ?string $sortBy = null,
        public ?string $sortDirection = null,
    ) {}

    /**
     * @return array{sort_by: string|null, sort_direction: string|null}
     */
    public function toArray(): array
    {
        return [
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
