<?php

declare(strict_types=1);

namespace Flatpack\Lists;

final readonly class FilterDefinition
{
    /**
     * @param  list<array{
     *     value: string,
     *     label: string,
     *     status?: 'success'|'pending'|'warning'|'error'|'info',
     *     icon?: string,
     * }>  $options
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $placeholder,
        public string $type,
        public bool $multiple,
        public ?string $mode = null,
        public array $options = [],
    ) {}

    public function isSelect(): bool
    {
        return $this->type === 'select';
    }

    public function isDate(): bool
    {
        return $this->type === 'date';
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     placeholder?: string,
     *     type: 'select'|'date',
     *     multiple: bool,
     *     mode?: 'exact'|'from',
     *     options?: list<array{
     *         value: string,
     *         label: string,
     *         status?: 'success'|'pending'|'warning'|'error'|'info',
     *         icon?: string,
     *     }>,
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'id' => $this->id,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'type' => $this->type,
            'multiple' => $this->multiple,
        ];

        if ($this->isSelect()) {
            $payload['options'] = $this->options;
        }

        if ($this->isDate() && $this->mode !== null) {
            $payload['mode'] = $this->mode;
        }

        return $payload;
    }
}
