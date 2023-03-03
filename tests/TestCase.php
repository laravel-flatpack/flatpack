<?php

namespace Flatpack\Tests;

use Flatpack\FlatpackServiceProvider;
use Illuminate\Encryption\Encrypter;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use WireUi\Providers\WireUiServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Flatpack\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
        */
    }

    protected function getPackageProviders($app)
    {
        return [
            FlatpackServiceProvider::class,
            WireUiServiceProvider::class,
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

        config()->set('app.key', Encrypter::generateKey(config('app.cipher')));

        include_once __DIR__.'/../database/migrations/create_test_tables.php.stub';
        (new \CreateTestTables())->up();
    }
}
