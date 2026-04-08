/**
 * Remove Vite output dirs so each production build replaces assets entirely.
 * Mirrors resolve logic in vite.config.ts (FLATPACK_PUBLIC_DEST + package public/build).
 */
import { rmSync } from 'node:fs';
import { basename, dirname, isAbsolute, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadEnv } from 'vite';

const packageRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');

function resolvePublicFlatpackDir(packageDir, raw) {
    const trimmed = raw?.trim();

    if (!trimmed) {
        return resolve(packageDir, 'public');
    }

    if (isAbsolute(trimmed)) {
        return resolve(trimmed);
    }

    const forMacHomePath = trimmed.replace(/^\.\//, '');
    if (/^Users[\\/]/.test(forMacHomePath)) {
        return resolve('/', forMacHomePath);
    }

    return resolve(packageDir, trimmed);
}

const env = loadEnv('development', packageRoot, '');
let hostPublic = resolvePublicFlatpackDir(packageRoot, env.FLATPACK_PUBLIC_DEST);
if (basename(hostPublic) === 'build') {
    console.warn(
        '[flatpack] FLATPACK_PUBLIC_DEST should be the folder that contains build/ (e.g. .../vendor/flatpack), not .../build. Using the parent directory.',
    );
    hostPublic = dirname(hostPublic);
}

const dirs = [resolve(hostPublic, 'build'), resolve(packageRoot, 'public/build')];
const seen = new Set();

for (const dir of dirs) {
    const key = resolve(dir);
    if (seen.has(key)) continue;
    seen.add(key);
    rmSync(key, { recursive: true, force: true });
}
