<?php

declare(strict_types=1);

namespace Flatpack;

use Closure;
use Flatpack\Actions\ActionModelClassResolver;
use Flatpack\Actions\DefaultActionResolver;
use Flatpack\Authorization\PolicyAwareFlatpackAuthorizer;
use Flatpack\Composition\CompositionValues;
use Flatpack\Composition\DefaultCompositionQuery;
use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\YamlCompositionLoader;
use Flatpack\Contracts\Actions\ActionResolver;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Contracts\Menu\MenuBuilder as MenuBuilderContract;
use Flatpack\Http\FlatpackRequest;
use Flatpack\Http\Middleware\ConfigureFlatpackViteAssets;
use Flatpack\Http\Middleware\SetFlatpackInertiaRootView;
use Flatpack\Http\Middleware\ShareFlatpackInertiaData;
use Flatpack\Lists\ListRecordsLoader;
use Flatpack\Menu\FlatpackMenuBuilder;
use Flatpack\Registration\AuthenticationRedirectCallbacks;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Override;
use Throwable;

final class FlatpackServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/flatpack.php', 'flatpack');
        $this->registerContainerBindings();
    }

    public function boot(): void
    {
        $this->syncCompiledAssetsFromPackageIfNeeded();
        $this->publishAssets();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerJsonExceptionHandling();
        $this->configureHttpKernel();
    }

    protected function registerContainerBindings(): void
    {
        $this->app->singleton(CompositionLoader::class, fn ($app): YamlCompositionLoader => new YamlCompositionLoader(
            $app->make('files'),
            (string) $app['config']->get('flatpack.path', base_path('flatpack')),
        ));

        $this->app->singleton(CompositionQuery::class, fn ($app): DefaultCompositionQuery => new DefaultCompositionQuery(
            $app->make(CompositionLoader::class),
        ));

        $this->app->singleton(CompositionValues::class, fn (): CompositionValues => new CompositionValues);

        $this->app->singleton(EntityComposition::class, fn ($app): EntityComposition => new EntityComposition(
            $app->make(CompositionQuery::class),
            $app->make(CompositionValues::class),
        ));

        $this->app->singleton(ListRecordsLoader::class, fn (): ListRecordsLoader => new ListRecordsLoader);

        $this->app->singleton(ActionModelClassResolver::class);

        $this->app->singleton(FlatpackAuthorizer::class, PolicyAwareFlatpackAuthorizer::class);
        $this->app->singleton(ActionResolver::class, DefaultActionResolver::class);
        $this->app->singleton(MenuBuilderContract::class, FlatpackMenuBuilder::class);

        $this->app->singleton(Flatpack::class, fn ($app): Flatpack => new Flatpack(
            menuBuilder: $app->make(MenuBuilderContract::class),
        ));
    }

    protected function configureHttpKernel(): void
    {
        $this->app->booted(function (): void {
            // Must resolve the HttpKernel contract: Laravel binds it separately from the
            // concrete Kernel class, so make(Kernel::class) mutates an unused instance.
            $kernel = $this->app->make(HttpKernelContract::class);
            if (! $kernel instanceof HttpKernel) {
                return;
            }

            $kernel->prependMiddlewareToGroup('web', ConfigureFlatpackViteAssets::class);
            $kernel->appendMiddlewareToGroup('web', ShareFlatpackInertiaData::class);
            $kernel->appendMiddlewareToGroup('web', SetFlatpackInertiaRootView::class);
            AuthenticationRedirectCallbacks::register();
        });
    }

    /**
     * Copy flatpack-package/public/build to the host when the manifest is missing
     * (e.g. fresh clone or CI without running npm run build).
     */
    protected function syncCompiledAssetsFromPackageIfNeeded(): void
    {
        $packageBuild = $this->packageBuildPath();
        if (! $this->shouldSyncCompiledAssets($packageBuild)) {
            return;
        }

        File::ensureDirectoryExists(public_path('vendor/flatpack'));
        File::copyDirectory($packageBuild, public_path('vendor/flatpack/build'));
    }

    protected function shouldSyncCompiledAssets(string $packageBuild): bool
    {
        if (! config('flatpack.sync_compiled_assets_from_package', true)) {
            return false;
        }

        if (is_file(public_path('vendor/flatpack/build/manifest.json'))) {
            return false;
        }

        if (is_file(public_path('vendor/flatpack/hot'))) {
            return false;
        }

        return is_file($packageBuild . '/manifest.json');
    }

    protected function packageBuildPath(): string
    {
        return dirname(__DIR__) . '/public/build';
    }

    protected function publishAssets(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/public' => public_path('vendor/flatpack'),
            dirname(__DIR__) . '/config/flatpack.php' => config_path('flatpack.php'),
        ], 'flatpack');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            // MakeCommand::class,
        ]);
    }

    protected function registerRoutes(): void
    {
        Route::middleware((array) config('flatpack.middleware', ['web']))
            ->prefix((string) config('flatpack.prefix', 'flatpack'))
            ->name('flatpack.')
            ->group(dirname(__DIR__) . '/routes/web.php');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(dirname(__DIR__) . '/resources/views', 'flatpack');
    }

    /**
     * Inertia / JSON Accept requests set expectsJson() true while unauthenticated users still
     * need a redirect to the Flatpack login page. Also register a renderable so we redirect
     * even when the auth middleware threw AuthenticationException with a null redirect URL
     * (expectsJson was true at throw time).
     */
    protected function registerJsonExceptionHandling(): void
    {
        if (! config('flatpack.register_json_exception_handler', true)) {
            return;
        }

        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->shouldRenderJsonWhen($this->shouldRenderJsonWhenCallback());
            $handler->renderable($this->authenticationRedirectRenderable());
        });
    }

    /**
     * @return Closure(Request, Throwable): bool
     */
    protected function shouldRenderJsonWhenCallback(): Closure
    {
        return function (Request $request, Throwable $exception): bool {
            if ($exception instanceof AuthenticationException) {
                if ($request->header('X-Inertia') || FlatpackRequest::matches($request)) {
                    return false;
                }
            }

            return $request->expectsJson();
        };
    }

    /**
     * @return Closure(AuthenticationException, mixed): mixed
     */
    protected function authenticationRedirectRenderable(): Closure
    {
        return function (AuthenticationException $exception, mixed $request): mixed {
            if (! $request instanceof Request) {
                return null;
            }

            if (! FlatpackRequest::matches($request)) {
                return null;
            }

            return redirect()->guest(route('flatpack.login'));
        };
    }
}
