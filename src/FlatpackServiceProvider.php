<?php

namespace Faustoq\Flatpack;

use Faustoq\Flatpack\Commands\MakeCommand;
use Faustoq\Flatpack\Commands\MakeFormCommand;
use Faustoq\Flatpack\Commands\MakeListCommand;
use Faustoq\Flatpack\Http\Livewire\Form;
use Faustoq\Flatpack\Http\Livewire\ImageUploader;
use Faustoq\Flatpack\Http\Livewire\Table;
use Faustoq\Flatpack\View\Components\ActionButton;
use Faustoq\Flatpack\View\Components\FormField;
use Faustoq\Flatpack\View\Components\InputField;
use Faustoq\Flatpack\View\Components\Layout;
use Faustoq\Flatpack\View\Components\Modal;
use Faustoq\Flatpack\View\Components\RelationField;
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

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            MakeFormCommand::class,
            MakeListCommand::class,
            MakeCommand::class,
        ]);
    }

    /**
     * Register routes.
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents()
    {
        Livewire::component('flatpack.table', Table::class);
        Livewire::component('flatpack.form', Form::class);
        Livewire::component('flatpack.image-uploader', ImageUploader::class);
    }

    /**
     * Register Blade view components.
     */
    protected function registerViewComponents()
    {
        $this->loadViewComponentsAs('flatpack', [
            ActionButton::class,
            FormField::class,
            InputField::class,
            RelationField::class,
            Layout::class,
            Modal::class,
        ]);
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');
    }

    /**
     * Configure route group.
     */
    protected function routeConfiguration()
    {
        return [
            'prefix' => config('flatpack.prefix', 'backend'),
            'middleware' => $this->getRouteMiddleware(),
        ];
    }

    /**
     * Compose the list of middleware to be applied to the routes.
     */
    private function getRouteMiddleware()
    {
        return collect([\Faustoq\Flatpack\Http\Middleware\FlatpackMiddleware::class])
            ->prepend(config('flatpack.middleware.before', []))
            ->push(config('flatpack.middleware.after', []))
            ->prepend('web')
            ->flatten()
            ->toArray();
    }
}
