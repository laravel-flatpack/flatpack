<?php

declare(strict_types=1);

namespace Flatpack\Menu;

use Flatpack\Composition\CompositionValues;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Contracts\Menu\MenuBuilder as MenuBuilderContract;
use Flatpack\Http\Controllers\ListController;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;

final readonly class MenuBuilder implements MenuBuilderContract
{
    public function __construct(
        private ConfigRepository $config,
        private CompositionQuery $compositions,
        private CompositionValues $compositionValues,
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
        $i = 0;

        foreach ($items as $slug => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = (string) ($entry['name'] ?? $slug);
            $route = (string) ($entry['route'] ?? '#');
            $icon = (string) ($entry['icon'] ?? 'folder');
            $sortOrder = $i;
            $key = is_string($slug) ? $slug : (string) $name;

            $result[] = new MenuItem(
                slug: $key,
                name: $name,
                icon: $icon,
                route: $route,
                sortOrder: $sortOrder,
            );

            $i++;
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

            $list = $this->compositions->optional($entry, 'list');
            $displayName = $this->compositionValues->displayName($list);
            $icon = $this->compositionValues->icon($list);
            $sortOrder = $this->compositionValues->sortOrder($list);

            $items[] = new MenuItem(
                slug: $entry,
                name: $displayName ?? Str::of($entry)
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->plural()
                    ->toString(),
                icon: $icon ?? 'folder',
                route: action([ListController::class, 'index'], ['entity' => $entry]),
                sortOrder: $sortOrder,
            );
        }

        return $this->sortMenuItems($items);
    }

    /**
     * @param  list<MenuItem>  $items
     * @return list<MenuItem>
     */
    private function sortMenuItems(array $items): array
    {
        usort($items, function (MenuItem $a, MenuItem $b): int {
            $byOrder = $a->sortOrder <=> $b->sortOrder;

            return $byOrder !== 0 ? $byOrder : strcasecmp($a->name, $b->name);
        });

        return $items;
    }
}
