<?php

namespace Faustoq\Flatpack;

use Faustoq\Flatpack\Commands\FlatpackCommand;
use Illuminate\Support\ServiceProvider;

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

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flatpack');

        $this->loadRoutesFrom(__DIR__ . '/routes/flatpack.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FlatpackCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flatpack.php', 'flatpack');
    }
}
