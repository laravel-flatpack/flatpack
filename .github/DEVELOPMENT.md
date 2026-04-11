# Development Guidelines

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

This document covers local frontend tooling for the **Flatpack Laravel package** (not a standalone Laravel app). Vite reads **only this package’s `.env`** (`envDir` in `vite.config.ts`); the host application’s `.env` is not used for `npm run dev` / `npm run build`.

## Frontend (Vite)

Flatpack ships an Inertia + React UI under `resources/js` and `resources/css`.

### Install

From the package root:

```bash
npm install
cp .env.example .env   # first time only
```

### Environment variables (package `.env`)

| Variable                                    | Purpose                                                                                                         |
| ------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `FLATPACK_APP_NAME`                         | Passed to the client as `import.meta.env.FLATPACK_APP_NAME`.                                                    |
| `FLATPACK_VITE_HOST` / `FLATPACK_VITE_PORT` | Dev server bind address and port (default **127.0.0.1** and **5174** so the host app’s Vite can keep **5173**). |
| `FLATPACK_PUBLIC_DEST`                      | Where Vite writes **`build/`** (manifest + hashed assets) and the **`hot`** file during dev. See below.         |

### Where assets go and how the host loads them

Flatpack publishes its **`public/`** tree with `php artisan vendor:publish --tag=flatpack`, which copies it to the host’s **`public/vendor/flatpack/`**. Laravel’s `@vite` integration is pointed at that tree by `ConfigureFlatpackViteAssets` (manifest at **`public/vendor/flatpack/build/manifest.json`**, hot file at **`public/vendor/flatpack/hot`** when using the dev server).

**Default (no `FLATPACK_PUBLIC_DEST`):** Vite writes to **`public/build`** inside this package. That matches what you ship and publish.

**Developing against a host app on disk** (path repo, sibling folder, etc.): set **`FLATPACK_PUBLIC_DEST`** to the host’s **published** directory — the folder that **contains** `build/` and `hot`, not the `build` folder itself:

```env
# Example: host app is next to this repo
FLATPACK_PUBLIC_DEST=../your-host-app/public/vendor/flatpack
```

Absolute paths are supported. If the last path segment is mistakenly `build`, the config warns and uses the parent directory.

When `FLATPACK_PUBLIC_DEST` points **outside** this package’s `public/`, each production build **syncs** `build/` back into **`public/build`** here so committed assets match what you tested on the host.

### Inertia and SSR

The Vite config uses **`inertia({ ssr: false })`**. Flatpack is **client-rendered** only; Inertia’s Vite SSR dev endpoint is disabled so `react-dom/client` is not evaluated in Node during `npm run dev`.

### Scripts

| Command             | Use case                                                                                                                                                                              |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`npm run dev`**   | Vite dev server + HMR. Prefer this while editing UI. Requires `FLATPACK_PUBLIC_DEST` aimed at the host’s `public/vendor/flatpack` if the host should load the dev server (via `hot`). |
| **`npm run watch`** | `vite build --watch`. Rebuilds production bundles on save. Use when the host must read **on-disk** assets from `vendor/flatpack/build` without running the Vite server.               |
| **`npm run build`** | One-off production build (release, CI, or refreshing `public/build` before commit).                                                                                                   |
| **`npm run clean`** | Deletes the configured `build/` output (and this package’s `public/build`) without compiling.                                                                                         |

`npm run build` and `npm run watch` run a **clean** of the relevant `build/` directories at the start of each compile so old hashed chunks are not left behind.

### Laravel Vite plugin notes

- The Laravel plugin may log **`APP_URL: undefined`** when this package’s `.env` has no `APP_URL`. It is harmless for package development; you can set `APP_URL` in the package `.env` if you want a value there.
- **Full reload** paths in `vite.config.ts` include `../routes/**/*.php` and `../app/**/*.php` relative to this package. Those only exist in a **monorepo-style** layout; in a standalone clone they simply match nothing.

### Linting and types

```bash
npm run lint
npm run types:check
```
