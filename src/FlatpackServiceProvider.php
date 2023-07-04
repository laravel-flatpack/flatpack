<?php

namespace Flatpack;

use Flatpack\Commands\ActionCommand;
use Flatpack\Commands\MakeCommand;
use Flatpack\Commands\WidgetCommand;
use Flatpack\Http\Livewire\BlockEditor;
use Flatpack\Http\Livewire\CreateRelation;
use Flatpack\Http\Livewire\Dashboard;
use Flatpack\Http\Livewire\Form;
use Flatpack\Http\Livewire\ImageUploader;
use Flatpack\Http\Livewire\SearchBox;
use Flatpack\Http\Livewire\Table;
use Flatpack\Http\Middleware\Authenticate;
use Flatpack\Http\Middleware\FlatpackMiddleware;
use Flatpack\View\Components\ActionButton;
use Flatpack\View\Components\FormField;
use Flatpack\View\Components\GuestLayout;
use Flatpack\View\Components\Layout;
use Flatpack\View\Components\Modal;
use Flatpack\View\Components\RelationField;
use Flatpack\View\Components\TagInput;
use Illuminate\Routing\Router;
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
        $this->registerMiddleware();
        $this->registerViews();
        $this->registerViewComponents();
        $this->registerLivewireComponents();
        $this->registerRoutes();
    }

    /**
     * Register Flatpack.
     */
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
            MakeCommand::class,
            ActionCommand::class,
            WidgetCommand::class,
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
        Livewire::component('flatpack.dashboard', Dashboard::class);
        Livewire::component('flatpack.table', Table::class);
        Livewire::component('flatpack.form', Form::class);
        Livewire::component('flatpack.create-relation', CreateRelation::class);
        Livewire::component('flatpack.image-uploader', ImageUploader::class);
        Livewire::component('flatpack.block-editor', BlockEditor::class);
        Livewire::component('flatpack.search-box', SearchBox::class);
    }

    /**
     * Register Blade view components.
     */
    protected function registerViewComponents()
    {
        $this->loadViewComponentsAs('flatpack', [
            GuestLayout::class,
            Layout::class,
            ActionButton::class,
            FormField::class,
            RelationField::class,
            TagInput::class,
            Modal::class,
        ]);

        $this->loadViewComponentsAs('flatpack-widget', config('flatpack.dashboard'));
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');
    }

    /**
     * Register route middleware.
     */
    protected function registerMiddleware()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('flatpack-auth', Authenticate::class);
        $router->aliasMiddleware('flatpack', FlatpackMiddleware::class);
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
        return collect(['web'])
            ->prepend(config('flatpack.middleware.before', []))
            ->push(config('flatpack.middleware.after', []))
            ->flatten()
            ->toArray();
    }
}
