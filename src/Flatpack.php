<?php

declare(strict_types=1);

namespace Flatpack;

use Composer\InstalledVersions;
use Flatpack\Services\Navigation\BreadcrumbsBuilder;
use Flatpack\Services\Navigation\MenuBuilder;
use Flatpack\Services\Navigation\MenuItem;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Internal package façade for shared config and navigation (resolved as a singleton). Prefer
 * {@see Facades\Flatpack} for static access inside the package (`Flatpack::method()`).
 *
 * Host applications should rely on routes, published config, and documented contracts rather than
 * resolving this type directly.
 *
 * @internal
 */
final readonly class Flatpack
{
    public function __construct(
        private MenuBuilder $menuBuilder,
        private BreadcrumbsBuilder $breadcrumbsBuilder,
        private Repository $config,
        private string $version,
    ) {}

    public static function composerPackageVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        try {
            $pretty = InstalledVersions::getPrettyVersion('flatpack/flatpack');
            if ($pretty !== null && $pretty !== '') {
                return $pretty;
            }

            $full = InstalledVersions::getVersion('flatpack/flatpack');
            if ($full !== null && $full !== '') {
                return $full;
            }
        } catch (Throwable) {
            // Package not present in InstalledVersions (e.g. non-Composer installs).
        }

        return 'dev';
    }

    /**
     * @return list<MenuItem>
     */
    public function menu(): array
    {
        return $this->sharedNavigation()['main'];
    }

    /**
     * @return list<array{label: string, href: string|null}>
     */
    public function breadcrumbs(Request $request): array
    {
        return $this->breadcrumbsBuilder->forRequest($request);
    }

    /**
     * Resolved Composer version when the package is installed as `flatpack/flatpack`, else `dev`.
     */
    public function version(): string
    {
        return $this->version;
    }

    public function routePrefix(): string
    {
        return trim((string) $this->config->get('flatpack.http.prefix', 'flatpack'), '/');
    }

    public function dashboardEntity(): string
    {
        return (string) $this->config->get('flatpack.composition.dashboard_entity', 'dashboard');
    }

    /**
     * Directory containing entity compositions (see `flatpack.composition.path`). Prefer {@see YamlCompositionLoader}
     * construction from config when avoiding circular references with the menu stack.
     */
    public function compositionPath(): string
    {
        return (string) $this->config->get('flatpack.composition.path', base_path('flatpack'));
    }

    public function defaultListPerPage(): int
    {
        return (int) $this->config->get('flatpack.lists.per_page', 10);
    }

    public function maxListPerPage(): int
    {
        return (int) $this->config->get('flatpack.lists.max_per_page', 100);
    }

    public function authGuard(): string
    {
        return (string) $this->config->get('flatpack.security.guard', 'web');
    }

    public function showActionShortcutHints(): bool
    {
        return (bool) $this->config->get('flatpack.ui.show_action_shortcut_hints', false);
    }

    public function quickAction(): mixed
    {
        return $this->config->get('flatpack.ui.quick_action');
    }

    public function secondaryMenu(): mixed
    {
        return $this->sharedNavigation()['secondary'];
    }

    public function bottomMenu(): mixed
    {
        return $this->sharedNavigation()['bottom'];
    }

    /**
     * @return array{main: list<MenuItem>, secondary: mixed, bottom: mixed}
     */
    private function sharedNavigation(): array
    {
        return once(fn (): array => $this->menuBuilder->resolveSharedNavigation(Auth::user()));
    }
}
