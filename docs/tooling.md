# Composer and backend tooling

From the package root with dev dependencies installed (`composer install`).

## Vitest (frontend)

Requires Node dependencies (`npm install`). Coverage uses **`@vitest/coverage-v8`** ([`vitest.config.ts`](../vitest.config.ts)): scoped includes under `resources/js/components/` (actions, form-fields, list-columns, shortcuts, widgets); shadcn **`components/ui/**`\*\* is excluded from coverage.

| Command                       | Purpose                                                                           |
| ----------------------------- | --------------------------------------------------------------------------------- |
| `npm run test`                | Run all Vitest specs once.                                                        |
| `npm run test:coverage`       | Same with **V8 coverage** (terminal summary + HTML under **`coverage/vitest/`**). |
| `npm run test:coverage:watch` | Watch mode with coverage.                                                         |

Open **`coverage/vitest/index.html`** for the HTML report. This path is separate from PHP’s **`coverage/`** (Pest) so the two reports do not overwrite each other.

## Pest and checks

| Command                  | Purpose                                                   |
| ------------------------ | --------------------------------------------------------- |
| `composer run test`      | Run the full Pest suite (`pest`).                         |
| `composer run check`     | Lint (Pint), Rector dry-run, PHPStan, then full Pest run. |
| `composer run lint`      | Laravel Pint.                                             |
| `composer run lint:test` | Pint in test (dry-run) mode.                              |
| `composer run phpstan`   | PHPStan analysis (`512M` memory limit).                   |
| `composer run rector`    | Rector dry-run.                                           |

## Schema keys (composition contract)

| Command                               | Purpose                                                                        |
| ------------------------------------- | ------------------------------------------------------------------------------ |
| `composer run schema:keys`            | Verify generated schema keys match JSON schemas (`--check`; no writes).        |
| `composer run schema:keys:generate`   | Regenerate PHP + TypeScript from `resources/schema/form.json` and `list.json`. |
| `composer run schema:contract:strict` | Run `CompositionSchemaContractTest` only.                                      |
| `composer run schema:ci:strict`       | Schema keys check + strict contract tests + TypeScript keys generator test.    |

See also [.github/DEVELOPMENT.md](../.github/DEVELOPMENT.md) for JSON schema workflow detail.

## PHP code coverage

Requires **Xdebug** with coverage enabled (e.g. `xdebug.mode=coverage` or `XDEBUG_MODE=coverage`; the scripts set `XDEBUG_MODE=coverage`).

| Command                           | Purpose                                                                                                                                                                           |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `composer run test-coverage`      | Run Pest with **terminal coverage summary** and write **`clover.xml`** at the package root (ignored by git). Uses a **512M** PHP memory limit so the coverage phase can complete. |
| `composer run test-coverage-html` | Same suite with **HTML** report under **`coverage/`** (gitignored). Open **`coverage/index.html`** in a browser for per-file detail.                                              |

CI generates Clover on PHP 8.3 and updates [`.github/badge-coverage.svg`](../.github/badge-coverage.svg) via the `tests` workflow; local runs are for developer feedback only.
