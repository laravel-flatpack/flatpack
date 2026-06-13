<?php

declare(strict_types=1);

namespace Flatpack\Navigation;

use Flatpack\Composition\CompositionValues;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Http\Controllers\ListController;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class FilesystemMenuSource
{
    public function __construct(
        private ConfigRepository $config,
        private CompositionQuery $compositions,
        private CompositionValues $compositionValues,
        private FlatpackAuthorizer $authorizer,
    ) {}

    /**
     * @return array{main: list<MenuItem>, secondary: list<MenuItem>, bottom: list<MenuItem>}
     */
    public function collectBuckets(bool $applyAuthorization = true, ?Authenticatable $user = null): array
    {
        $basePath = (string) $this->config->get('flatpack.composition.path', base_path('flatpack'));
        $dashboardEntity = trim((string) $this->config->get('flatpack.composition.dashboard_entity', 'dashboard'));
        if ($dashboardEntity === '') {
            $dashboardEntity = 'dashboard';
        }
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
            if (! $this->canIncludeListInMenu($list, $applyAuthorization, $user)) {
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
                url: $entry === $dashboardEntity
                    ? route('flatpack.dashboard')
                    : action([ListController::class, 'index'], ['entity' => $entry]),
                icon: $icon ?? 'folder',
                navOrder: $navOrder,
            );

            $buckets[$placement][] = $menuItem;
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>|null  $list
     */
    private function canIncludeListInMenu(
        ?array $list,
        bool $applyAuthorization = true,
        ?Authenticatable $user = null,
    ): bool {
        if (! $applyAuthorization) {
            return true;
        }

        $modelClass = $this->compositionValues->modelClass($list);
        if (! is_string($modelClass) || trim($modelClass) === '') {
            return true;
        }
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return false;
        }
        if ($user === null) {
            return false;
        }

        return $this->authorizer->allows($user, 'viewAny', $modelClass)
            || $this->authorizer->allows($user, 'viewAll', $modelClass);
    }
}
