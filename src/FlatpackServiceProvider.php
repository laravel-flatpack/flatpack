<?php

namespace Faustoq\Flatpack;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Faustoq\Flatpack\Commands\MakeCommand;
use Faustoq\Flatpack\Commands\MakeFormCommand;
use Faustoq\Flatpack\Commands\MakeListCommand;
use Faustoq\FlatPack\Http\Middleware\FlatpackMiddleware;

class FlatpackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/public' => public_path('vendor/flatpack'),
        ], 'public');

        $this->publishes([
            __DIR__ . '/../config/flatpack.php' => config_path('flatpack.php'),
        ], 'config');

        $this->registerViews();

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFormCommand::class,
                MakeListCommand::class,
                MakeCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flatpack.php', 'flatpack');
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('flatpack.prefix', 'backend'),
            'middleware' => config('flatpack.middleware', FlatpackMiddleware::class),
        ];
    }
}
