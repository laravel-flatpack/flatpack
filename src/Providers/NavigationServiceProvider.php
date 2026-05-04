<?php

declare(strict_types=1);

namespace Flatpack\Providers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Navigation\BreadcrumbsBuilder;
use Flatpack\Navigation\FlatpackMenuBuilder;
use Flatpack\Navigation\MenuBuilder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Menu and breadcrumb graph bindings (depends on {@see EntityComposition}).
 */
final class NavigationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(BreadcrumbsBuilder::class, fn ($app): BreadcrumbsBuilder => new BreadcrumbsBuilder(
            $app->make(EntityComposition::class),
            $app->make(Repository::class),
        ));

        $this->app->singleton(MenuBuilder::class, FlatpackMenuBuilder::class);
    }
}
