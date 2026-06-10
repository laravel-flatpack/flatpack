<?php

declare(strict_types=1);

use Flatpack\Console\Support\InjectUserCanAccessFlatpackService;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Illuminate\Support\Facades\File;

uses(TestCase::class);

/**
 * @return array{class: class-string, path: string, dir: string}
 */
function createTempUserModelWithoutFlatpackAccess(): array
{
    $className = 'TempUserWithoutFlatpack_' . bin2hex(random_bytes(8));
    $fqcn = 'Flatpack\\Tests\\Stubs\\' . $className;
    $dir = sys_get_temp_dir() . '/flatpack-user-stub-' . bin2hex(random_bytes(8));
    File::ensureDirectoryExists($dir);
    $path = $dir . '/' . $className . '.php';

    File::put($path, <<<PHP
<?php

declare(strict_types=1);

namespace Flatpack\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class {$className} extends Authenticatable
{
    protected \$table = 'users';
}
PHP);

    require_once $path;

    return [
        'class' => $fqcn,
        'path' => $path,
        'dir' => $dir,
    ];
}

test('inject user can access flatpack service reports no injection when method already exists', function () {
    config()->set('auth.providers.users.model', User::class);

    $service = app(InjectUserCanAccessFlatpackService::class);

    expect($service->needsInjection())->toBeFalse()
        ->and($service->inject())->toBeFalse();
});

test('inject user can access flatpack service reports injection needed when method is absent', function () {
    $stub = createTempUserModelWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);

        $service = app(InjectUserCanAccessFlatpackService::class);

        expect($service->needsInjection())->toBeTrue();
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('inject user can access flatpack service injects boilerplate method once', function () {
    $stub = createTempUserModelWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);

        $service = app(InjectUserCanAccessFlatpackService::class);

        expect($service->inject())->toBeTrue();

        $source = File::get($stub['path']);
        expect($source)->toContain('public function canAccessFlatpack(): bool')
            ->toContain('return true; // tighten for your app (role, admin flag, etc.)')
            ->and(mb_substr_count($source, 'function canAccessFlatpack'))->toBe(1)
            ->and($service->needsInjection())->toBeFalse()
            ->and($service->inject())->toBeFalse();
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('inject user can access flatpack service resolves path from configured auth provider model', function () {
    $stub = createTempUserModelWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);

        $service = app(InjectUserCanAccessFlatpackService::class);

        expect($service->resolveUserModelClass())->toBe($stub['class'])
            ->and(realpath((string) $service->resolveUserModelPath()))->toBe(realpath($stub['path']));
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('inject user can access flatpack service falls back to app models user class name', function () {
    config()->set('auth.providers.users.model', null);

    $service = app(InjectUserCanAccessFlatpackService::class);

    expect($service->resolveUserModelClass())->toBe('App\\Models\\User');
});
