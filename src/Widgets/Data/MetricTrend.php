<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class MetricTrend implements Arrayable
{
    /**
     * @param  'up'|'down'|'flat'  $direction
     */
    public function __construct(
        public string $direction,
        public float|int $percent,
        public ?string $comment = null,
    ) {}

    /**
     * @return array{
     *     direction: 'up'|'down'|'flat',
     *     percent: float|int,
     *     comment: string
     * }
     */
    public function toArray(): array
    {
        return [
            'direction' => $this->direction,
            'percent' => $this->percent,
            'comment' => $this->comment,
        ];
    }
}
