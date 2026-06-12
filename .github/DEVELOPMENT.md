# Development Guidelines

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

This document covers local frontend tooling for the **Flatpack Laravel package** (not a standalone Laravel app). Vite reads **only this package’s `.env`** (`envDir` in `vite.config.ts`); the host application’s `.env` is not used for `npm run dev` / `npm run build`.

## JSON Schema and generated PHP constants

Forms and lists normalization read **allowlists** derived from `resources/schema/form.json` and `resources/schema/list.json`. When you add or rename a top-level key, field type, column shape, header action property, or bulk-action property, regenerate the committed PHP file **after** editing the JSON.

From the package root (with dev dependencies installed, so `vendor/bin/testbench` exists):

The repo ships **`testbench.yaml`** registering `Flatpack\Providers\FlatpackServiceProvider`. If you maintain a **custom** `testbench.yaml`, merge in that `providers` entry—otherwise Orchestra Testbench loads your file **instead of** defaults and Artisan will have no `flatpack:*` commands.

```bash
composer run schema:keys:generate
```

That **writes** `src/Schema/Generated/CompositionSchemaKeys.php`, `resources/js/lib/generated/composition-schema-keys.ts`, and formats the TypeScript with **Biome** when `node_modules/.bin/biome` exists. Commit both generated files with your schema change.

**`composer run schema:keys`** runs the same Artisan command **with `--check`** only: it verifies generated files match the JSON schemas and **does not write anything**. Use it locally or in CI before merge.

In an application that depends on Flatpack, the same command is registered as Artisan:

```bash
php artisan flatpack:generate-composition-schema-keys
```

| Flag / script                           | Purpose                                                                                                                                                                                                                             |
| --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **`--check`**                           | Exits with a non-zero status if the JSON schema’s extracted key sets no longer match **`src/Schema/Generated/CompositionSchemaKeys.php`** or **`resources/js/lib/generated/composition-schema-keys.ts`**. Does not write any files. |
| **`composer run schema:keys`**          | Runs **`--check`** via Testbench (same as CI): verify only, no writes.                                                                                                                                                              |
| **`composer run schema:keys:generate`** | Regenerates PHP + TS from **`resources/schema/form.json`** and **`list.json`** (no `--check`).                                                                                                                                      |
| **`composer run check`**                | Runs lint, static analysis, **`schema:keys`** (verify), and tests.                                                                                                                                                                  |

The React bundle does **not** import full `form.json` / `list.json` (would inflate the client bundle). It imports **`resources/js/lib/generated/composition-schema-keys.ts`**, emitted by the same Artisan command as the PHP file. **`form-schema-contract.ts`** re-exports field-type allowlists from that module for `normalizeFields`.

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

## Vitest (frontend)

Requires Node dependencies (`npm install`). Coverage uses **`@vitest/coverage-v8`** (`vitest.config.ts`).

| Command | Purpose |
| --- | --- |
| `npm run test` | Run all Vitest specs once. |
| `npm run test:coverage` | Same with V8 coverage (terminal summary + HTML under `coverage/vitest/`). |
| `npm run test:coverage:watch` | Watch mode with coverage. |

## Composer checks

| Command | Purpose |
| --- | --- |
| `composer run test` | Full Pest suite. |
| `composer run check` | Lint (Pint), Rector dry-run, PHPStan, schema keys verify, Pest. |
| `composer run lint` | Laravel Pint. |
| `composer run phpstan` | PHPStan (`512M` memory limit). |
| `composer run schema:contract:strict` | `CompositionSchemaContractTest` only. |
| `composer run test-coverage` | Pest with Clover (`clover.xml`; requires Xdebug coverage mode). |
| `composer run test-coverage-html` | Same + HTML under `coverage/`. |

## Test ownership

Use one canonical test layer per behavior — avoid duplicating the same assertion in feature and unit tests.

| Behavior family | Canonical location |
| --- | --- |
| Schema shape / key validity | `tests/Unit/CompositionSchemaContractTest.php` |
| HTTP endpoint semantics | `tests/Feature/*` |
| Authorization HTTP contracts | `tests/Feature/Authorization/*` |
| Parser / runtime coercion | `resources/js/lib/*.test.ts` |
| UI interaction behavior | `resources/js/components/**/*.test.tsx` |

- Do not reassert full parser normalization payloads in feature tests unless the endpoint contract depends on them.
- Keep feature tests focused on externally observable behavior: status codes, response envelopes, persistence effects, redirects.
