import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, isAbsolute, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv  } from 'vite';
import type {Plugin} from 'vite';

const packageRoot = dirname(fileURLToPath(import.meta.url));

/**
 * Where Vite writes `build/` + `hot` for the consuming Laravel app's public tree.
 * Environment variables come only from this package's `.env` (see `envDir`).
 *
 * - Default: `/public/flatpack` relative to this package directory.
 * - FLATPACK_PUBLIC_DEST: absolute path, or path relative to this package directory.
 */
function resolvePublicFlatpackDir(
    packageDir: string,
    raw: string | undefined,
): string {
    const trimmed = raw?.trim();

    if (!trimmed) {
        return resolve(packageDir, 'public/flatpack');
    }

    if (isAbsolute(trimmed)) {
        return resolve(trimmed);
    }

    if (/^Users[\\/]/.test(trimmed)) {
        return resolve('/', trimmed);
    }

    return resolve(packageDir, trimmed);
}

export default defineConfig(({ mode, command }) => {
    const env = loadEnv(mode, packageRoot, '');
    /** Must match the public URL segment where Flatpack assets are served (see flatpack::app @vite). */
    const flatpackPublicBase = (env.VITE_FLATPACK_BASE || '/vendor/flatpack/build/').replace(
        /\/?$/,
        '/',
    );
    const hostPublicFlatpack = resolvePublicFlatpackDir(
        packageRoot,
        env.FLATPACK_PUBLIC_DEST,
    );

    const syncBuildToPackage = (): Plugin => ({
        name: 'flatpack-sync-build-to-package',
        apply: 'build',
        enforce: 'post',
        closeBundle() {
            setImmediate(() => {
                const src = resolve(hostPublicFlatpack, 'build');
                const dest = resolve(packageRoot, 'public/build');

                if (!existsSync(resolve(src, 'manifest.json'))) {
                    return;
                }

                mkdirSync(dirname(dest), { recursive: true });
                cpSync(src, dest, { recursive: true });
                console.log(`[flatpack] Synced Vite build to ${dest}`);
            });
        },
    });

    return {
        root: packageRoot,
        envDir: packageRoot,
        // Without this, Rollup can emit dynamic imports against /build/... while Laravel serves from vendor/flatpack/build.
        base: command === 'build' ? flatpackPublicBase : '',
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                publicDirectory: hostPublicFlatpack,
                buildDirectory: 'build',
                refresh: [
                    {
                        paths: [
                            'resources/views/**/*.blade.php',
                            resolve(packageRoot, '../routes/**/*.php'),
                            resolve(packageRoot, '../app/**/*.php'),
                        ],
                    },
                ],
            }),
            inertia(),
            react(),
            tailwindcss(),
            syncBuildToPackage(),
        ],
        resolve: {
            // Force a single React instance (package entry vs resolved deps).
            dedupe: ['react', 'react-dom', 'scheduler'],
            alias: {
                '@': resolve(packageRoot, 'resources/js'),
                react: resolve(packageRoot, 'node_modules/react'),
                'react-dom': resolve(packageRoot, 'node_modules/react-dom'),
                'react-dom/client': resolve(packageRoot, 'node_modules/react-dom/client.js'),
                'react/jsx-runtime': resolve(packageRoot, 'node_modules/react/jsx-runtime.js'),
                'react/jsx-dev-runtime': resolve(packageRoot, 'node_modules/react/jsx-dev-runtime.js'),
                scheduler: resolve(packageRoot, 'node_modules/scheduler'),
            },
        },
        server: {
            host: env.VITE_HOST || '127.0.0.1',
            port: env.VITE_PORT ? Number(env.VITE_PORT) : 5174,
            strictPort: true,
            cors: true,
        },
    };
});
