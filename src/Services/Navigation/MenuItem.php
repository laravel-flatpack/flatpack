<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

final readonly class MenuItem
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $url,
        public string $icon = 'menu',
        public int $navOrder = 99,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
