<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

final readonly class CardWidgetData implements WidgetPayload
{
    public function __construct(
        public float|int|string|null $value = null,
        public ?string $context = null,
        public ?string $footer = null,
    ) {}

    /**
     * @return array{
     *     value?: float|int|string|null,
     *     context?: string|null,
     *     footer?: string|null
     * }
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'value' => $this->value,
                'context' => $this->context,
                'footer' => $this->footer,
            ],
            static fn (mixed $v): bool => $v !== null,
        );
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | (int) $options);
    }
}
