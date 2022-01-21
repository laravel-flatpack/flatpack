<?php

namespace Faustoq\Flatpack;

use Illuminate\Support\ServiceProvider;
use Faustoq\Flatpack\Commands\FlatpackCommand;

class FlatpackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/public' => public_path('vendor/flatpack'),
        ], 'public');

        $this->publishes([
            __DIR__ . '/../config/flatpack.php' => config_path('flatpack.php')
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlatpackCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flatpack.php', 'flatpack');
    }
}
