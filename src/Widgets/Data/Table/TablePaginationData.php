<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data\Table;

use Flatpack\Widgets\Data\WidgetPayload;

final readonly class TablePaginationData implements WidgetPayload
{
    public function __construct(
        public int $currentPage = 1,
        public int $lastPage = 1,
        public int $perPage = 5,
        public int $total = 0,
        public ?int $from = null,
        public ?int $to = null,
    ) {}

    /**
     * @return array{
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
