<?php

declare(strict_types=1);

namespace Flatpack\Menu;

final readonly class MenuItem
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $route,
        public string $icon = 'menu',
        public int $sortOrder = 99,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'route' => $this->route,
            'icon' => $this->icon,
        ];
    }
}
