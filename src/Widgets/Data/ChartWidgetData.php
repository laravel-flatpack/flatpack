<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

final readonly class ChartWidgetData implements WidgetPayload
{
    /**
     * @param  list<array<string, int|float|string|null>>  $points
     */
    public function __construct(public array $points) {}

    /**
     * @return array{points: list<array<string, int|float|string|null>>}
     */
    public function toArray(): array
    {
        return ['points' => $this->points];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
