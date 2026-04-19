<?php

declare(strict_types=1);

namespace Flatpack;

use Composer\InstalledVersions;
use Flatpack\Contracts\Menu\MenuBuilder;
use Flatpack\Menu\MenuItem;
use Illuminate\Contracts\Config\Repository;
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
        return $this->menuBuilder->build();
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
        return trim((string) $this->config->get('flatpack.prefix', 'flatpack'), '/');
    }

    public function dashboardEntity(): string
    {
        return (string) $this->config->get('flatpack.dashboard_entity', 'dashboard');
    }

    /**
     * Directory containing entity compositions (see `flatpack.path`). Prefer {@see YamlCompositionLoader}
     * construction from config when avoiding circular references with the menu stack.
     */
    public function compositionPath(): string
    {
        return (string) $this->config->get('flatpack.path', base_path('flatpack'));
    }

    public function defaultListPerPage(): int
    {
        return (int) $this->config->get('flatpack.list.per_page', 10);
    }

    public function maxListPerPage(): int
    {
        return (int) $this->config->get('flatpack.list.max_per_page', 100);
    }

    public function authGuard(): string
    {
        return (string) $this->config->get('flatpack.guard', 'web');
    }

    public function showActionShortcutHints(): bool
    {
        return (bool) $this->config->get('flatpack.ui.show_action_shortcut_hints', false);
    }

    public function quickAction(): mixed
    {
        return $this->config->get('flatpack.quick_action');
    }

    public function secondaryMenu(): mixed
    {
        return $this->config->get('flatpack.secondary_menu');
    }

    public function bottomMenu(): mixed
    {
        return $this->config->get('flatpack.bottom_menu');
    }
}
