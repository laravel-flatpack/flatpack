<?php

namespace Faustoq\Flatpack\Tests;

use Faustoq\Flatpack\FlatpackServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Faustoq\\Flatpack\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
        */
    }

    protected function getPackageProviders($app)
    {
        return [
            FlatpackServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    public function ignorePackageDiscoveriesFrom()
    {
        return [];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('view.paths', [
                __DIR__.'/../views',
                resource_path('views'),
            ]);

        config()->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-flatpack_table.php.stub';
        $migration->up();
        */
    }
}
