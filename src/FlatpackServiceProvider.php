<?php

namespace Faustoq\Flatpack;

use Faustoq\Flatpack\Commands\MakeCommand;
use Faustoq\Flatpack\Commands\MakeFormCommand;
use Faustoq\Flatpack\Commands\MakeListCommand;
use Faustoq\Flatpack\Http\Livewire\Table;
use Faustoq\Flatpack\Http\Middleware\FlatpackMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FlatpackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('flatpack'),
        ], 'public');

        $this->publishes([
            __DIR__ . '/../config/flatpack.php' => config_path('flatpack.php'),
        ], 'config');

        $this->commands([
            MakeFormCommand::class,
            MakeListCommand::class,
            MakeCommand::class
        ]);

        $this->registerViews();
        $this->registerRoutes();
        $this->registerComponents();
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

    protected function registerComponents()
    {
        Livewire::component('flatpack.table', Table::class);
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('flatpack.prefix', 'backend'),
            'middleware' => config('flatpack.middleware', FlatpackMiddleware::class),
        ];
    }
}
