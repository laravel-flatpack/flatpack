<?php

declare(strict_types=1);

use Flatpack\Tests\InstallFlatpackTestCase;
use Flatpack\Tests\Models\User;
use Illuminate\Support\Facades\File;

uses(InstallFlatpackTestCase::class);

const FLATPACK_REINSTALL_CONFIRMATION = 'Flatpack looks already installed. Would you like to re-install? This will overwrite existing config files.';

function seedFlatpackAlreadyInstalled(string $configContents = "<?php\nreturn ['marker' => 'existing'];\n"): void
{
    File::ensureDirectoryExists(dirname(config_path('flatpack.php')));
    File::put(config_path('flatpack.php'), $configContents);
    File::ensureDirectoryExists(public_path('vendor/flatpack'));
}

/**
 * @return array{class: class-string, path: string, dir: string}
 */
function installTestUserStubWithoutFlatpackAccess(): array
{
    $className = 'InstallUserWithoutFlatpack_' . bin2hex(random_bytes(8));
    $fqcn = 'Flatpack\\Tests\\Stubs\\' . $className;
    $dir = sys_get_temp_dir() . '/flatpack-install-user-' . bin2hex(random_bytes(8));
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

test('flatpack:install publishes flatpack config and assets', function () {
    $configPath = config_path('flatpack.php');
    $assetsPath = public_path('vendor/flatpack');

    if (is_file($configPath)) {
        File::delete($configPath);
    }

    if (is_dir($assetsPath)) {
        File::deleteDirectory($assetsPath);
    }

    $this->artisan('flatpack:install', ['--no-interaction' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Flatpack is installed.');

    expect(is_file($configPath))->toBeTrue()
        ->and(is_dir($assetsPath))->toBeTrue();
});

test('flatpack:install overwrites published config when force is passed', function () {
    seedFlatpackAlreadyInstalled();

    $this->artisan('flatpack:install', [
        '--force' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $published = File::get(config_path('flatpack.php'));
    expect($published)->toContain("'composition'")
        ->not->toContain("'marker' => 'existing'");
});

test('flatpack:install skips republish when already installed and reinstall is declined', function () {
    seedFlatpackAlreadyInstalled();

    $this->artisan('flatpack:install')
        ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
        ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
        ->assertSuccessful();

    expect(File::get(config_path('flatpack.php')))->toContain("'marker' => 'existing'");
});

test('flatpack:install republishes when already installed and reinstall is confirmed', function () {
    seedFlatpackAlreadyInstalled();

    $this->artisan('flatpack:install')
        ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'yes')
        ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
        ->assertSuccessful();

    expect(File::get(config_path('flatpack.php')))->toContain("'composition'")
        ->not->toContain("'marker' => 'existing'");
});

test('flatpack:install skips republish when already installed and non interactive', function () {
    seedFlatpackAlreadyInstalled();

    $this->artisan('flatpack:install', ['--no-interaction' => true])
        ->assertSuccessful();

    expect(File::get(config_path('flatpack.php')))->toContain("'marker' => 'existing'");
});

test('flatpack:install publishes ai skill when confirmed', function () {
    $skillPath = base_path('.ai/skills/flatpack-host-yaml-authoring');
    seedFlatpackAlreadyInstalled();

    if (is_dir($skillPath)) {
        File::deleteDirectory($skillPath);
    }

    $this->artisan('flatpack:install')
        ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
        ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'yes')
        ->assertSuccessful();

    expect(is_dir($skillPath))->toBeTrue();
});

test('flatpack:install skips ai skill publish when declined', function () {
    $skillPath = base_path('.ai/skills/flatpack-host-yaml-authoring');
    seedFlatpackAlreadyInstalled();

    if (is_dir($skillPath)) {
        File::deleteDirectory($skillPath);
    }

    $this->artisan('flatpack:install')
        ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
        ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
        ->assertSuccessful();

    expect(is_dir($skillPath))->toBeFalse();
});

test('flatpack:install injects canAccessFlatpack when confirmed', function () {
    seedFlatpackAlreadyInstalled();
    $stub = installTestUserStubWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);

        $this->artisan('flatpack:install')
            ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
            ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
            ->expectsConfirmation('Add canAccessFlatpack() to ' . $stub['class'] . ' now?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('Added canAccessFlatpack() returning true.');

        expect(File::get($stub['path']))->toContain('public function canAccessFlatpack(): bool');
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('flatpack:install skips user injection when declined and reminds in summary', function () {
    seedFlatpackAlreadyInstalled();
    $stub = installTestUserStubWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);
        $original = File::get($stub['path']);

        $this->artisan('flatpack:install')
            ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
            ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
            ->expectsConfirmation('Add canAccessFlatpack() to ' . $stub['class'] . ' now?', 'no')
            ->assertSuccessful()
            ->expectsOutputToContain('Add canAccessFlatpack() to your User model before visiting the panel.');

        expect(File::get($stub['path']))->toBe($original);
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('flatpack:install skips user injection when method already exists', function () {
    seedFlatpackAlreadyInstalled();
    config()->set('auth.providers.users.model', User::class);

    $path = (new ReflectionClass(User::class))->getFileName();
    expect($path)->not->toBeFalse();

    $original = File::get($path);

    $this->artisan('flatpack:install')
        ->expectsConfirmation(FLATPACK_REINSTALL_CONFIRMATION, 'no')
        ->expectsConfirmation('Publish Flatpack AI YAML authoring skill?', 'no')
        ->assertSuccessful();

    expect(File::get($path))->toBe($original);
});

test('flatpack:install no interaction skips prompts and prints next steps', function () {
    seedFlatpackAlreadyInstalled();
    $stub = installTestUserStubWithoutFlatpackAccess();

    try {
        config()->set('auth.providers.users.model', $stub['class']);
        $original = File::get($stub['path']);

        $this->artisan('flatpack:install', ['--no-interaction' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Open the panel: /flatpack')
            ->expectsOutputToContain('php artisan flatpack:make Post')
            ->expectsOutputToContain('Add canAccessFlatpack() to your User model before visiting the panel.');

        expect(File::get($stub['path']))->toBe($original);
    } finally {
        File::deleteDirectory($stub['dir']);
    }
});

test('flatpack:install next steps output includes panel url and flatpack make hint', function () {
    config()->set('flatpack.http.prefix', 'admin');
    config()->set('auth.providers.users.model', User::class);

    $this->artisan('flatpack:install', ['--no-interaction' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Open the panel: /admin')
        ->expectsOutputToContain('php artisan flatpack:make Post')
        ->expectsOutputToContain('.docs/host-installation.md');
});

test('flatpack:install output does not mention inertia policies or composition path creation', function () {
    config()->set('auth.providers.users.model', User::class);

    $this->artisan('flatpack:install', ['--no-interaction' => true])
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Inertia')
        ->doesntExpectOutputToContain('policy')
        ->doesntExpectOutputToContain('composition path');
});
