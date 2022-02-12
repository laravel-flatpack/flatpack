<?php

namespace Faustoq\Flatpack;

use Faustoq\Flatpack\Commands\MakeCommand;
use Faustoq\Flatpack\Commands\MakeFormCommand;
use Faustoq\Flatpack\Commands\MakeListCommand;
use Faustoq\Flatpack\Http\Livewire\Form;
use Faustoq\Flatpack\Http\Livewire\Table;
use Faustoq\Flatpack\View\Components\FormField;
use Faustoq\Flatpack\View\Components\Layout;
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


        $this->registerCommands();
        $this->registerViews();
        $this->registerViewComponents();
        $this->registerLivewireComponents();
        $this->registerRoutes();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flatpack.php', 'flatpack');

        $this->app->bind('flatpack', function ($app) {
            return new Flatpack();
        });
    }

    protected function registerCommands()
    {
        $this->commands([
            MakeFormCommand::class,
            MakeListCommand::class,
            MakeCommand::class,
        ]);
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function registerLivewireComponents()
    {
        Livewire::component('flatpack.table', Table::class);
        Livewire::component('flatpack.form', Form::class);
    }

    protected function registerViewComponents()
    {
        $this->loadViewComponentsAs('flatpack', [
            Layout::class,
            FormField::class,
        ]);
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');
    }

    protected function routeConfiguration()
    {
        $middleware = collect(config('flatpack.middleware', [
            \Faustoq\Flatpack\Http\Middleware\FlatpackMiddleware::class
        ]))->prepend('web')->toArray();

        return [
            'prefix' => config('flatpack.prefix', 'backend'),
            'middleware' => $middleware,
        ];
    }
}
