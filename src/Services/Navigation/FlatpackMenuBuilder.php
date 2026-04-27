<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

use Flatpack\Composition\CompositionValues;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Http\Controllers\ListController;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final readonly class FlatpackMenuBuilder implements MenuBuilder
{
    public function __construct(
        private ConfigRepository $config,
        private CompositionQuery $compositions,
        private CompositionValues $compositionValues,
        private FlatpackAuthorizer $authorizer,
    ) {}

    /**
     * @return array{main: list<MenuItem>, secondary: mixed, bottom: mixed}
     */
    public function resolveSharedNavigation(): array
    {
        $buckets = $this->collectFilesystemBuckets();

        $mainOverride = $this->config->get('flatpack.ui.navigation.main');
        $main = match (true) {
            $mainOverride === null => $buckets['main'],
            is_array($mainOverride) => $this->menuFromConfig($mainOverride),
            default => $buckets['main'],
        };

        $secondaryRaw = $this->config->get('flatpack.ui.navigation.secondary');
        $secondary = is_array($secondaryRaw)
            ? $this->resolveSidebarGroup($secondaryRaw, $buckets['secondary'])
            : $this->sidebarGroupFromItems($buckets['secondary']);

        $bottomRaw = $this->config->get('flatpack.ui.navigation.bottom');
        $bottom = is_array($bottomRaw)
            ? $this->resolveSidebarGroup($bottomRaw, $buckets['bottom'])
            : $this->sidebarGroupFromItems($buckets['bottom']);

        return [
            'main' => $main,
            'secondary' => $secondary,
            'bottom' => $bottom,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<MenuItem>
     */
    private function menuFromConfig(array $items): array
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

            $result[] = new MenuItem(
                slug: $key,
                name: $name,
                url: $url,
                icon: $icon,
            );
        }

        return $result;
    }

    /**
     * @return array{main: list<MenuItem>, secondary: list<MenuItem>, bottom: list<MenuItem>}
     */
    private function collectFilesystemBuckets(): array
    {
        $basePath = (string) $this->config->get('flatpack.composition.path', base_path('flatpack'));
        $buckets = [
            'main' => [],
            'secondary' => [],
            'bottom' => [],
        ];

        if (! is_dir($basePath)) {
            return $buckets;
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
            if (! $this->canIncludeListInMenu($list)) {
                continue;
            }

            $placement = $this->compositionValues->listNavigationMenu($list);

            $displayName = $this->compositionValues->displayName($list);
            $icon = $this->compositionValues->icon($list);
            $navOrder = $this->compositionValues->navOrder($list);

            $menuItem = new MenuItem(
                slug: $entry,
                name: $displayName ?? Str::of($entry)
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->plural()
                    ->toString(),
                url: action([ListController::class, 'index'], ['entity' => $entry]),
                icon: $icon ?? 'folder',
                navOrder: $navOrder,
            );

            $buckets[$placement][] = $menuItem;
        }

        foreach (array_keys($buckets) as $key) {
            $buckets[$key] = $this->sortMenuItems($buckets[$key]);
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $override
     * @param  list<MenuItem>  $filesystemItems
     */
    private function resolveSidebarGroup(array $override, array $filesystemItems): ?array
    {
        $isWrapper = array_key_exists('items', $override) || array_key_exists('label', $override);

        if (! $isWrapper) {
            $built = $this->menuFromConfig($override);
            if ($built === []) {
                return null;
            }

            return [
                'items' => array_map(
                    static fn (MenuItem $item): array => $item->toArray(),
                    $built,
                ),
            ];
        }

        $label = null;
        if (isset($override['label']) && is_string($override['label']) && trim($override['label']) !== '') {
            $label = trim($override['label']);
        }

        $itemsConfig = $override['items'] ?? null;

        if ($itemsConfig === null) {
            return $this->sidebarGroupFromItems($filesystemItems, $label);
        }

        if (! is_array($itemsConfig)) {
            return $this->sidebarGroupFromItems($filesystemItems, $label);
        }

        $built = $this->menuFromConfig($itemsConfig);
        $serialized = array_map(
            static fn (MenuItem $item): array => $item->toArray(),
            $built,
        );

        if ($serialized === [] && $label === null) {
            return null;
        }

        $out = ['items' => $serialized];
        if ($label !== null) {
            $out['label'] = $label;
        }

        return $out;
    }

    /**
     * @param  list<MenuItem>  $items
     * @return array{label?: string, items: list<array<string, string>>}|null
     */
    private function sidebarGroupFromItems(array $items, ?string $label = null): ?array
    {
        if ($items === []) {
            return $label === null ? null : ['label' => $label, 'items' => []];
        }

        $out = [
            'items' => array_map(
                static fn (MenuItem $item): array => $item->toArray(),
                $items,
            ),
        ];
        if ($label !== null) {
            $out['label'] = $label;
        }

        return $out;
    }

    /**
     * @param  list<MenuItem>  $items
     * @return list<MenuItem>
     */
    private function sortMenuItems(array $items): array
    {
        usort($items, function (MenuItem $a, MenuItem $b): int {
            $byOrder = $a->navOrder <=> $b->navOrder;

            return $byOrder !== 0 ? $byOrder : strcasecmp($a->name, $b->name);
        });

        return $items;
    }

    /**
     * @param  array<string, mixed>|null  $list
     */
    private function canIncludeListInMenu(?array $list): bool
    {
        $modelClass = $this->compositionValues->modelClass($list);
        if (! is_string($modelClass) || trim($modelClass) === '') {
            return true;
        }
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return false;
        }
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->authorizer->allows($user, 'viewAny', $modelClass)
            || $this->authorizer->allows($user, 'viewAll', $modelClass);
    }
}
