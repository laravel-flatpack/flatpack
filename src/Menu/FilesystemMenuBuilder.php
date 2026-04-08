<?php

declare(strict_types=1);

namespace Flatpack\Menu;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Flatpack\Contracts\Menu\MenuBuilder;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;

final readonly class FilesystemMenuBuilder implements MenuBuilder
{
    public function __construct(
        private ConfigRepository $config,
        private CompositionLoader $compositionLoader,
    ) {}

    public function build(): array
    {
        $override = $this->config->get('flatpack.menu');

        if (is_array($override) && $override !== []) {
            return $this->menuFromConfig($override);
        }

        return $this->menuFromFilesystem();
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<MenuItem>
     */
    private function menuFromConfig(array $items): array
    {
        $result = [];

        foreach ($items as $slug => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = (string) ($entry['name'] ?? $slug);
            $route = (string) ($entry['route'] ?? '#');
            $icon = (string) ($entry['icon'] ?? 'menu');
            $key = is_string($slug) ? $slug : (string) $name;

            $result[] = new MenuItem(
                slug: $key,
                name: $name,
                route: $route,
                icon: $icon,
            );
        }

        return $result;
    }

    /**
     * @return list<MenuItem>
     */
    private function menuFromFilesystem(): array
    {
        $basePath = (string) $this->config->get('flatpack.path', base_path('flatpack'));
        $items = [];

        if (! is_dir($basePath)) {
            return [];
        }

        foreach (scandir($basePath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $dir = $basePath . DIRECTORY_SEPARATOR . $entry;

            if (! is_dir($dir)) {
                continue;
            }

            $name = $this->resolveEntityName($entry);

            $items[] = new MenuItem(
                slug: $entry,
                name: Str::of($name)->plural()->title()->toString(),
                route: 'flatpack.' . $entry . '.index',
                icon: 'menu',
            );
        }

        usort($items, fn (MenuItem $a, MenuItem $b): int => strcmp($a->name, $b->name));

        return $items;
    }

    private function resolveEntityName(string $slug): string
    {
        try {
            $data = $this->compositionLoader->load($slug, 'form');

            if (isset($data['name']) && is_string($data['name'])) {
                return $data['name'];
            }
        } catch (CompositionNotFoundException) {
            try {
                $data = $this->compositionLoader->load($slug, 'list');

                if (isset($data['name']) && is_string($data['name'])) {
                    return $data['name'];
                }
            } catch (CompositionNotFoundException) {
                // fall through
            }
        }

        return ucfirst(str_replace(['-', '_'], ' ', $slug));
    }
}
