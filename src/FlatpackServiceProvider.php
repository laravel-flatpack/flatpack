<?php

namespace Flatpack;

use Flatpack\Commands\MakeCommand;
use Flatpack\Commands\MakeFormCommand;
use Flatpack\Commands\MakeListCommand;
use Flatpack\Http\Livewire\BlockEditor;
use Flatpack\Http\Livewire\CreateRelation;
use Flatpack\Http\Livewire\Form;
use Flatpack\Http\Livewire\ImageUploader;
use Flatpack\Http\Livewire\Table;
use Flatpack\View\Components\ActionButton;
use Flatpack\View\Components\FormField;
use Flatpack\View\Components\InputField;
use Flatpack\View\Components\Layout;
use Flatpack\View\Components\Modal;
use Flatpack\View\Components\RelationField;
use Flatpack\View\Components\TagInput;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FlatpackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('flatpack'),
            __DIR__ . '/../config/flatpack.php' => config_path('flatpack.php'),
        ], 'flatpack');

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
        Livewire::component('flatpack.create-relation', CreateRelation::class);
        Livewire::component('flatpack.image-uploader', ImageUploader::class);
        Livewire::component('flatpack.block-editor', BlockEditor::class);
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
            TagInput::class,
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
        return collect([\Flatpack\Http\Middleware\FlatpackMiddleware::class])
            ->prepend(config('flatpack.middleware.before', []))
            ->push(config('flatpack.middleware.after', []))
            ->prepend('web')
            ->flatten()
            ->toArray();
    }
}
