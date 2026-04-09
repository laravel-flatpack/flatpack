<?php

declare(strict_types=1);

namespace Flatpack\Menu;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Flatpack\Contracts\Menu\MenuBuilder;
use Flatpack\Http\Controllers\FlatpackListController;
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
            $icon = (string) ($entry['icon'] ?? 'folder');
            $key = is_string($slug) ? $slug : (string) $name;

            $result[] = new MenuItem(
                slug: $key,
                name: $name,
                icon: $icon,
                route: $route,
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

            $items[] = new MenuItem(
                slug: $entry,
                name: $this->resolveEntityName($entry),
                icon: $this->resolveEntityIcon($entry),
                route: action([FlatpackListController::class, 'index'], ['entity' => $entry]),
            );
        }

        usort($items, fn (MenuItem $a, MenuItem $b): int => strcmp($a->name, $b->name));

        return $items;
    }

    private function resolveEntityName(string $slug): string
    {
        try {
            $data = $this->compositionLoader->load($slug, 'list');

            if (isset($data['name']) && is_string($data['name'])) {
                return $data['name'];
            }
        } catch (CompositionNotFoundException) {
            //
        }

        return Str::of($slug)
            ->replace(['-', '_'], ' ')
            ->title()
            ->plural()
            ->toString();
    }

    private function resolveEntityIcon(string $slug): string
    {
        try {
            $data = $this->compositionLoader->load($slug, 'list');

            if (isset($data['icon']) && is_string($data['icon'])) {
                return $data['icon'];
            }
        } catch (CompositionNotFoundException) {
            //
        }

        return 'folder';
    }
}
