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

    public function build(): array
    {
        $override = $this->config->get('flatpack.menu');

        if ($override === null) {
            return $this->menuFromFilesystem();
        }

        if (is_array($override)) {
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
        $allowExternalOrigins = (bool) $this->config->get('flatpack.navigation.allow_external_origins', false);

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
                icon: $icon,
                url: $url,
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

            $list = $this->compositions->optional($entry, 'list');
            if (! $this->canIncludeListInMenu($list)) {
                continue;
            }
            $displayName = $this->compositionValues->displayName($list);
            $icon = $this->compositionValues->icon($list);
            $navOrder = $this->compositionValues->navOrder($list);

            $items[] = new MenuItem(
                slug: $entry,
                name: $displayName ?? Str::of($entry)
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->plural()
                    ->toString(),
                icon: $icon ?? 'folder',
                url: action([ListController::class, 'index'], ['entity' => $entry]),
                navOrder: $navOrder,
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
