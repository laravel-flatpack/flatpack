<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Payloads;

use Flatpack\Widgets\Data\MetricTrend;
use Illuminate\Contracts\Support\Arrayable;

final readonly class MetricWidgetData implements Arrayable
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
     *         comment: string
     *     },
     *     description: string
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
}
