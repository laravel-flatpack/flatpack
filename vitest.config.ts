import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

const root = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [react()],
    test: {
        environment: 'jsdom',
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/**/*.test.ts', 'resources/js/**/*.test.tsx'],
        css: true,
        coverage: {
            provider: 'v8',
            /** text-summary = compact totals first; text = per-file table; json-summary = coverage-summary.json */
            reporter: ['text-summary', 'text', 'html', 'json-summary'],
            /** Avoid overwriting Pest HTML report under `coverage/` (see composer test-coverage-html). */
            reportsDirectory: './coverage/vitest',
            exclude: [
                'resources/js/components/ui/**',
            ],
            include: [
                'resources/js/components/actions/**',
                'resources/js/components/form-fields/**',
                'resources/js/components/list-columns/**',
                'resources/js/components/shortcuts/**',
                'resources/js/components/widgets/**',
            ],
        },
    },
    resolve: {
        dedupe: ['react', 'react-dom', 'scheduler'],
        alias: {
            '@': resolve(root, 'resources/js'),
            react: resolve(root, 'node_modules/react'),
            'react-dom': resolve(root, 'node_modules/react-dom'),
            'react-dom/client': resolve(root, 'node_modules/react-dom/client.js'),
            'react/jsx-runtime': resolve(root, 'node_modules/react/jsx-runtime.js'),
            'react/jsx-dev-runtime': resolve(
                root,
                'node_modules/react/jsx-dev-runtime.js',
            ),
            scheduler: resolve(root, 'node_modules/scheduler'),
            dashjs: resolve(root, 'resources/js/shims/dashjs-cdn.ts'),
        },
    },
});
