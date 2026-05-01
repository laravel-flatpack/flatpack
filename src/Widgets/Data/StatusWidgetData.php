<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

final readonly class StatusWidgetData implements WidgetPayload
{
    public function __construct(
        public Status $status,
        public float|int|string $value,
        public ?string $context,
        public ?string $updated_at,
        public ?string $description = null,
    ) {}

    /**
     * @return array{
     *     status: 'default'|'error'|'info'|'success'|'warning',
     *     value: float|int|string,
     *     context: string|null,
     *     updated_at: string|null,
     *     description?: string|null
     * }
     */
    public function toArray(): array
    {
        $out = [
            'status' => $this->status->value,
            'value' => $this->value,
            'context' => $this->context,
            'updated_at' => $this->updated_at,
        ];
        if ($this->description !== null) {
            $out['description'] = $this->description;
        }

        return $out;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
