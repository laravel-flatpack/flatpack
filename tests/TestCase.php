<?php

namespace Faustoq\Flatpack\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Faustoq\Flatpack\FlatpackServiceProvider;

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
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-flatpack_table.php.stub';
        $migration->up();
        */
    }
}
