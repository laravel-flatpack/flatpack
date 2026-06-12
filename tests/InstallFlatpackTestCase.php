<?php

declare(strict_types=1);

namespace Flatpack\Tests;

use RuntimeException;

use function Orchestra\Testbench\default_skeleton_path;

/**
 * Install command tests publish config and assets through Laravel's config_path()
 * and public_path(). In Testbench those resolve under the shared default skeleton,
 * which breaks parallel Pest workers when one test deletes flatpack.php while
 * another bootstraps. This case uses a per-process isolated application root.
 */
abstract class InstallFlatpackTestCase extends TestCase
{
    /**
     * @var list<string>
     */
    private const array SKELETON_SYMLINKS = [
        'app',
        'artisan',
        'bootstrap',
        'composer.json',
        'database',
        'flatpack',
        'lang',
        'migrations',
        'resources',
        'routes',
        'server.php',
        'storage',
        'vendor',
    ];

    private static ?string $isolatedBasePath = null;

    final public static function applicationBasePath(): string
    {
        return self::isolatedBasePath();
    }

    private static function isolatedBasePath(): string
    {
        if (self::$isolatedBasePath !== null && is_dir(self::$isolatedBasePath)) {
            return self::$isolatedBasePath;
        }

        $skeleton = default_skeleton_path();
        $base = sys_get_temp_dir() . '/flatpack-install-app-' . getmypid();

        if (! is_dir($base)) {
            self::provisionIsolatedApplication($skeleton, $base);
        }

        return self::$isolatedBasePath = $base;
    }

    private static function provisionIsolatedApplication(string $skeleton, string $base): void
    {
        if (! mkdir($base, 0777, true) && ! is_dir($base)) {
            throw new RuntimeException("Unable to create isolated install application path [{$base}].");
        }

        foreach (self::SKELETON_SYMLINKS as $segment) {
            $target = $base . '/' . $segment;

            if (file_exists($target)) {
                continue;
            }

            if (! symlink($skeleton . '/' . $segment, $target)) {
                throw new RuntimeException("Unable to symlink [{$segment}] into isolated install application.");
            }
        }

        $envSource = $skeleton . '/.env';

        if (is_file($envSource) && ! is_file($base . '/.env')) {
            copy($envSource, $base . '/.env');
        }

        $configDirectory = $base . '/config';

        if (! is_dir($configDirectory) && ! mkdir($configDirectory, 0777, true) && ! is_dir($configDirectory)) {
            throw new RuntimeException("Unable to create isolated install config directory [{$configDirectory}].");
        }

        foreach (glob($skeleton . '/config/*.php') ?: [] as $configFile) {
            if (basename($configFile) === 'flatpack.php') {
                continue;
            }

            $destination = $configDirectory . '/' . basename($configFile);

            if (! is_file($destination)) {
                copy($configFile, $destination);
            }
        }

        if (! is_dir($base . '/public') && ! mkdir($base . '/public', 0777, true) && ! is_dir($base . '/public')) {
            throw new RuntimeException('Unable to create isolated install public directory.');
        }

        if (! is_dir($base . '/.ai/skills') && ! mkdir($base . '/.ai/skills', 0777, true) && ! is_dir($base . '/.ai/skills')) {
            throw new RuntimeException('Unable to create isolated install AI skills directory.');
        }
    }
}
