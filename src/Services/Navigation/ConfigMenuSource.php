<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final readonly class ConfigMenuSource
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<MenuItem>
     */
    public function fromConfigItems(array $items): array
    {
        $result = [];
        $allowExternalOrigins = (bool) $this->config->get('flatpack.ui.allow_external_navigation_urls', false);

        foreach ($items as $slug => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = (string) ($entry['name'] ?? $slug);
            $url = NavigationUrl::sanitize((string) ($entry['url'] ?? ''), $allowExternalOrigins);
            if ($url === '') {
                continue;
            }
            $icon = (string) ($entry['icon'] ?? 'folder');
            $key = is_string($slug) ? $slug : (string) $name;
            $navOrder = isset($entry['nav_order']) && is_numeric($entry['nav_order'])
                ? (int) $entry['nav_order']
                : 99;

            $result[] = new MenuItem(
                slug: $key,
                name: $name,
                url: $url,
                icon: $icon,
                navOrder: $navOrder,
            );
        }

        return $result;
    }
}
