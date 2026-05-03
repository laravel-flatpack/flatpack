<?php

declare(strict_types=1);

namespace Flatpack\Tests;

use Flatpack\Providers\FlatpackServiceProvider;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\Policies\PostPolicy;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionProperty;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Post::class, PostPolicy::class);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Flatpack\\Tests\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        $this->app->make('view')->replaceNamespace('flatpack', [__DIR__ . '/Support/views']);

        Inertia::setRootView('flatpack::app');
    }

    final public function ignorePackageDiscoveriesFrom(): array
    {
        return [];
    }

    /**
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
            FlatpackServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Reset current session auth.
     */
    protected function resetAuth(?array $guards = null): void
    {
        $guards = $guards !== null && $guards !== [] ? $guards : array_keys(config('auth.guards'));

        foreach ($guards as $guard) {
            $guard = $this->app['auth']->guard($guard);

            if ($guard instanceof SessionGuard) {
                $guard->logout();
            }
        }

        $protectedProperty = new ReflectionProperty($this->app['auth'], 'guards');
        $protectedProperty->setValue($this->app['auth'], []);
    }
}
