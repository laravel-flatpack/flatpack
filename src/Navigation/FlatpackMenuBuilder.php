<?php

declare(strict_types=1);

namespace Flatpack\Navigation;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final readonly class FlatpackMenuBuilder implements MenuBuilder
{
    public function __construct(
        private ConfigRepository $config,
        private ConfigMenuSource $configMenuSource,
        private FilesystemMenuSource $filesystemMenuSource,
    ) {}

    /**
     * @return array{main: list<MenuItem>, secondary: mixed, bottom: mixed}
     */
    public function resolveSharedNavigation(?Authenticatable $user = null): array
    {
        $menuItemsByBucket = $this->resolvedMenuItemsByBucket(
            applyAuthorization: true,
            user: $user,
            sortResolvedItems: true,
        );
        $secondaryRaw = $this->config->get('flatpack.ui.navigation.secondary');
        $secondary = is_array($secondaryRaw)
            ? $this->resolveSidebarGroup($secondaryRaw, $menuItemsByBucket['secondary'])
            : $this->sidebarGroupFromItems($menuItemsByBucket['secondary']);

        $bottomRaw = $this->config->get('flatpack.ui.navigation.bottom');
        $bottom = is_array($bottomRaw)
            ? $this->resolveSidebarGroup($bottomRaw, $menuItemsByBucket['bottom'])
            : $this->sidebarGroupFromItems($menuItemsByBucket['bottom']);

        return [
            'main' => $menuItemsByBucket['main'],
            'secondary' => $secondary,
            'bottom' => $bottom,
        ];
    }

    /**
     * @return array{main: list<MenuItem>, secondary: list<MenuItem>, bottom: list<MenuItem>}
     */
    public function resolveMenuItemsByBucket(bool $applyAuthorization = true, ?Authenticatable $user = null): array
    {
        return $this->resolvedMenuItemsByBucket($applyAuthorization, $user, sortResolvedItems: true);
    }

    /**
     * @return array{main: list<MenuItem>, secondary: list<MenuItem>, bottom: list<MenuItem>}
     */
    private function resolvedMenuItemsByBucket(
        bool $applyAuthorization = true,
        ?Authenticatable $user = null,
        bool $sortResolvedItems = false,
    ): array {
        $buckets = $this->filesystemMenuSource->collectBuckets($applyAuthorization, $user);

        $mainOverride = $this->config->get('flatpack.ui.navigation.main');
        $main = match (true) {
            $mainOverride === null => $buckets['main'],
            is_array($mainOverride) => $this->configMenuSource->fromConfigItems($mainOverride),
            default => $buckets['main'],
        };

        $secondary = $this->resolveSidebarItems(
            $this->config->get('flatpack.ui.navigation.secondary'),
            $buckets['secondary'],
        );

        $bottom = $this->resolveSidebarItems(
            $this->config->get('flatpack.ui.navigation.bottom'),
            $buckets['bottom'],
        );

        if (! $sortResolvedItems) {
            return [
                'main' => $main,
                'secondary' => $secondary,
                'bottom' => $bottom,
            ];
        }

        return [
            'main' => $this->sortMenuItems($main),
            'secondary' => $this->sortMenuItems($secondary),
            'bottom' => $this->sortMenuItems($bottom),
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     * @param  list<MenuItem>  $filesystemItems
     */
    private function resolveSidebarGroup(array $override, array $filesystemItems): ?array
    {
        $isWrapper = array_key_exists('items', $override) || array_key_exists('label', $override);

        if (! $isWrapper) {
            $built = $this->configMenuSource->fromConfigItems($override);
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

        $built = $this->configMenuSource->fromConfigItems($itemsConfig);
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
     * @param  list<MenuItem>  $filesystemItems
     * @return list<MenuItem>
     */
    private function resolveSidebarItems(mixed $override, array $filesystemItems): array
    {
        if (! is_array($override)) {
            return $filesystemItems;
        }

        $isWrapper = array_key_exists('items', $override) || array_key_exists('label', $override);
        if (! $isWrapper) {
            return $this->configMenuSource->fromConfigItems($override);
        }

        $itemsConfig = $override['items'] ?? null;
        if ($itemsConfig === null || ! is_array($itemsConfig)) {
            return $filesystemItems;
        }

        return $this->configMenuSource->fromConfigItems($itemsConfig);
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
}
