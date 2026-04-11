import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { basename, dirname, isAbsolute, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';
import type { Plugin } from 'vite';

const packageRoot = dirname(fileURLToPath(import.meta.url));

/**
 * Where Vite writes `build/` + `hot` for the consuming Laravel app's public tree.
 * Environment variables come only from this package's `.env` (see `envDir`).
 *
 * - Default: `/public` relative to this package directory (build output in `public/build`).
 * - FLATPACK_PUBLIC_DEST: absolute path, or path relative to this package directory.
 *   Must be the directory that **contains** `build/` and `hot` (e.g. `.../public/vendor/flatpack`),
 *   not `.../public/vendor/flatpack/build`.
 *
 * Resolved paths are converted to a path relative to the package root before passing to
 * `laravel-vite-plugin` (e.g. `../your-app/public/vendor/flatpack` when developing against a host app).
 */
function resolvePublicFlatpackDir(
    packageDir: string,
    raw: string | undefined,
): string {
    const trimmed = raw?.trim();

    if (!trimmed) {
        return resolve(packageDir, 'public');
    }

    if (isAbsolute(trimmed)) {
        return resolve(trimmed);
    }

    // `./Users/...` must not resolve under packageDir (would create repo/Users/.../public).
    const forMacHomePath = trimmed.replace(/^\.\//, '');
    if (/^Users[\\/]/.test(forMacHomePath)) {
        return resolve('/', forMacHomePath);
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
    let hostPublicFlatpackAbs = resolvePublicFlatpackDir(
        packageRoot,
        env.FLATPACK_PUBLIC_DEST,
    );
    if (basename(hostPublicFlatpackAbs) === 'build') {
        console.warn(
            '[flatpack] FLATPACK_PUBLIC_DEST should be the folder that contains build/ and hot (e.g. .../vendor/flatpack), not .../build. Using the parent directory.',
        );
        hostPublicFlatpackAbs = dirname(hostPublicFlatpackAbs);
    }
    const laravelPublicDirectory = relative(packageRoot, hostPublicFlatpackAbs).replace(
        /\\/g,
        '/',
    );

    /**
     * Full replace of `build/` on every production build (including watch iterations).
     * Needed for a Laravel package: published `public/build` must not accumulate stale hashed chunks.
     */
    const cleanFlatpackOutDirs = (): Plugin => ({
        name: 'flatpack-clean-out-dirs',
        apply: 'build',
        buildStart() {
            const dirs = [
                resolve(hostPublicFlatpackAbs, 'build'),
                resolve(packageRoot, 'public/build'),
            ];
            const seen = new Set<string>();
            for (const dir of dirs) {
                const key = resolve(dir);
                if (seen.has(key)) continue;
                seen.add(key);
                rmSync(key, { recursive: true, force: true });
            }
        },
    });

    const syncBuildToPackage = (): Plugin => ({
        name: 'flatpack-sync-build-to-package',
        apply: 'build',
        enforce: 'post',
        closeBundle() {
            setImmediate(() => {
                const src = resolve(hostPublicFlatpackAbs, 'build');
                const dest = resolve(packageRoot, 'public/build');

                if (!existsSync(resolve(src, 'manifest.json'))) {
                    return;
                }

                if (resolve(src) === resolve(dest)) {
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
            cleanFlatpackOutDirs(),
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                publicDirectory: laravelPublicDirectory || '.',
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
            // Client-rendered Inertia only. Do not enable Vite's SSR dev endpoint (it would
            // evaluate react-dom/client in Node and fail with "module is not defined").
            inertia({ ssr: false }),
            react(),
            tailwindcss(),
            syncBuildToPackage(),
        ],
        resolve: {
            // Force a single React instance (package entry vs resolved deps).
            dedupe: ['react', 'react-dom', 'scheduler'],
            alias: {
                '@': resolve(packageRoot, 'resources/js'),
                // dash-video-element dynamically imports dashjs; serve UMD from CDN instead of bundling.
                dashjs: resolve(packageRoot, 'resources/js/shims/dashjs-cdn.ts'),
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
