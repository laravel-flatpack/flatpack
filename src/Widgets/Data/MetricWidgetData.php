<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

final readonly class MetricWidgetData implements WidgetPayload
{
    public function __construct(
        public float|int $value,
        public MetricTrend $trend,
        public ?string $description,
    ) {}

    /**
     * @return array{
     *     value: float|int,
     *     trend: array{
     *         direction: 'up'|'down'|'flat',
     *         percent: float|int,
     *         comment: string|null
     *     },
     *     description: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'trend' => $this->trend->toArray(),
            'description' => $this->description,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
