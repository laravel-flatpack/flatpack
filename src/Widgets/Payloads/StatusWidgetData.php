<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Payloads;

use Flatpack\Widgets\Data\Status;
use Illuminate\Contracts\Support\Arrayable;

final readonly class StatusWidgetData implements Arrayable
{
    public function __construct(
        public Status $status,
        public float|int|string $value,
        public ?string $context,
        public ?string $updated_at,
    ) {}

    /**
     * @return array{
     *     status: 'default'|'error'|'info'|'success'|'warning',
     *     value: float|int|string,
     *     context: string|null,
     *     updated_at: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'value' => $this->value,
            'context' => $this->context,
            'updated_at' => $this->updated_at,
        ];
    }
}
