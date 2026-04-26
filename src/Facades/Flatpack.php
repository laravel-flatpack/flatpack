<?php

declare(strict_types=1);

namespace Flatpack\Facades;

use Flatpack\Flatpack as FlatpackBinding;
use Illuminate\Support\Facades\Facade;

/**
 * Static proxy for the {@see FlatpackBinding} container singleton.
 *
 * @method static array<int, \Flatpack\Services\Navigation\MenuItem> menu()
 * @method static string version()
 * @method static string routePrefix()
 * @method static string dashboardEntity()
 * @method static string compositionPath()
 * @method static int defaultListPerPage()
 * @method static int maxListPerPage()
 * @method static string authGuard()
 * @method static bool showActionShortcutHints()
 * @method static mixed quickAction()
 * @method static mixed secondaryMenu()
 * @method static mixed bottomMenu()
 * @method static array<int, array{label: string, href: string|null}> breadcrumbs(\Illuminate\Http\Request $request)
 */
final class Flatpack extends Facade
{
    /**
     * Composer-installed package version, or `dev` when not discoverable.
     */
    public static function composerPackageVersion(): string
    {
        return FlatpackBinding::composerPackageVersion();
    }

    protected static function getFacadeAccessor(): string
    {
        return FlatpackBinding::class;
    }
}
