<?php

namespace Flatpack\Tests;

use Illuminate\Auth\SessionGuard;
use Illuminate\Encryption\Encrypter;
use Orchestra\Testbench\TestCase as Orchestra;

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
            \Flatpack\FlatpackServiceProvider::class,
            \WireUi\Providers\WireUiServiceProvider::class,
            \Rappasoft\LaravelLivewireTables\LaravelLivewireTablesServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
        ];
    }

    public function ignorePackageDiscoveriesFrom()
    {
        return [];
    }

    /**
     * Setup testing environment.
     *
     * @return void
     */
    public function getEnvironmentSetUp($app): void
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

    /**
     * Reset current session auth.
     *
     * @return void
     */
    protected function resetAuth(array $guards = null): void
    {
        $guards = $guards ?: array_keys(config('auth.guards'));

        foreach ($guards as $guard) {
            $guard = $this->app['auth']->guard($guard);

            if ($guard instanceof SessionGuard) {
                $guard->logout();
            }
        }

        $protectedProperty = new \ReflectionProperty($this->app['auth'], 'guards');
        $protectedProperty->setAccessible(true);
        $protectedProperty->setValue($this->app['auth'], []);
    }
}
