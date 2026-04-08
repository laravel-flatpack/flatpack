# Development Guidelines

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

You can find here some instructions that you may find useful during the local development. Happy coding!

## Frontend development (Vite)

Flatpack ships its own Inertia + React frontend under `resources/js` and `resources/css`. Vite is configured in this package; environment for the toolchain lives in **`flatpack-package/.env`** (not the host Laravel `.env`). Copy `.env.example` to `.env` and adjust as needed.

### Install dependencies

From the package directory:

```bash
npm install
cp .env.example .env   # first time only
```

### Where compiled assets are written

Production builds and the dev server write into a **public directory** that Laravel will serve. By default this is **`public/flatpack`** inside the package (`flatpack-package/public/flatpack`), which is suitable when you publish assets with Artisan.

When you develop against a Laravel app in the same repository (for example `flatpack-package` next to the app root), set **`FLATPACK_PUBLIC_DEST`** in `flatpack-package/.env` so Vite writes straight into the host’s web root, for example:

```env
# Relative to flatpack-package/ — typical path-repo layout (sibling app)
FLATPACK_PUBLIC_DEST=../public/vendor/flatpack
```

You can also use an absolute path. The Laravel app expects the Vite manifest at **`public/vendor/flatpack/build/manifest.json`** (see the `flatpack::app` Blade layout and `ConfigureFlatpackViteAssets` middleware), so the directory you set must end up as that `public/vendor/flatpack` tree on disk.

After each production build, the Vite config also mirrors the build output into **`flatpack-package/public/build`** so you can commit compiled assets with the package if you choose.

### Watch mode (rebuild on save)

While you edit components, pages, or styles, run:

```bash
npm run watch
```

This runs `vite build --watch`: on every change, assets are recompiled and written to the directory resolved from **`FLATPACK_PUBLIC_DEST`** (plus `build/` and `hot` when applicable). Keep this process running alongside your PHP application.

### Dev server (HMR)

For full Vite hot module replacement during development:

```bash
npm run dev
```

Ensure `VITE_HOST` and `VITE_PORT` in `flatpack-package/.env` match how you access the Vite server, and that the Laravel app’s Flatpack middleware can read the **`hot`** file under the same public directory you configured. The default port is **5174** so it does not clash with the host app’s Vite on **5173**.

### One-off production build

```bash
npm run build
```
