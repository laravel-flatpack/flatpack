---
name: Flatpack coding assistant
description: Applies Flatpack engineering conventions for Laravel, Inertia React, typing, refactors, testing, and tooling. Use when editing Flatpack code, reviewing changes, or enforcing project coding preferences.
---

# Flatpack Engineering Preferences

This skill captures project conventions and preferences established during recent refactoring work.

## Core Principles

- Keep code DRY; avoid duplication across components and utilities.
- Prefer simple, readable code over clever or complex abstractions.
- Optimize for maintainability and clear ownership of concerns.
- Favor self-explanatory code over explanatory comments.
- Preserve behavior when refactoring; improve structure first, comments last.

## Comments Policy

- Default: avoid narrative or historical comments.
- Use comments only for true exceptions where intent cannot be made clear via naming or structure.
- If a section needs a long comment to explain behavior, refactor the code instead.

## Package Context

- Flatpack is a Laravel admin package with an Inertia + React frontend.
- Entity behavior is composition-driven from YAML files in the configured `flatpack.path`.
- Main package entry points are under `src/`, `routes/`, `config/`, and `resources/js/`.

## Type Organization

- Shared/exported type definitions must live under `resources/js/types/`.
- Component files should keep only truly local/internal types.
- Prefer feature-based type files (for example: `types/data-table.ts`, `types/table.ts`, `types/upload.ts`).

## PHP Conventions

- Use `declare(strict_types=1)` in PHP files.
- Prefer constructor injection and focused services/controllers.
- Keep backend classes small, explicit, and single-purpose.
- Follow package-wide style through `pint` rather than manual formatting preferences.

## Data Table Conventions

- Data-table-related constants must be centralized in:
    - `resources/js/components/table/data-table-constants.ts`
- Avoid inline duplicated literals when a reusable constant is appropriate.
- Keep table rendering paths unified where possible (shared rendering + minimal branching).
- Keep schema parsing and runtime helpers in dedicated libs (`list-schema`, `data-table-utils`), not page components.

## Flatpack Architecture Notes

- List page flow: prefixed route -> controller -> composition/schema loader -> records loader -> Inertia page.
- Form page currently supports create/edit flow and schema loading; UI implementation can evolve independently.
- Shared Flatpack Inertia data (menu, quick action, auth user, core route links) should be injected centrally.
- JSON mode (`?json=true`) is a supported output mode and must stay consistent with page behavior.

## YAML and Schema Rules

- YAML compositions are the source of truth for entities.
- Relation columns require consistent relation metadata (`relation`, `relation_name`/`relationName`, `relation_value`/`relationValue`).
- List actions from YAML must be normalized/sanitized server-side before frontend rendering.

## Testing Conventions

- Frontend tests: `vitest`
- Backend tests: `pest`
- For validation after substantive frontend changes, run both:
    - `npm run test`
    - `vendor/bin/pest`
- Avoid flaky UI tests: prefer waiting for stable rendered UI over transient suspense/fallback states.
- For lazy-loaded module tests, prefer deterministic injected/mock loaders over relying on real dynamic import timing.

## Backend Tooling Preferences

- Code style/formatting: `pint`
- Automated refactors: `rector --dry-run` (review-first workflow)
- Static analysis: `phpstan analyse --memory-limit=512M`
- Preferred backend check order:
    - `pint`
    - `rector --dry-run`
    - `phpstan analyse --memory-limit=512M`
    - `pest`
- Keep backend checks clean before merging.

## Frontend Tooling Preferences

- Formatting/linting is Biome-driven (`resources/js`, `resources/css` scope).
- Keep TypeScript strictness enabled; avoid weakening compiler options to bypass issues.
- Use `npm run check` for day-to-day frontend gates, and `npm run check:all` for full package validation.

## TypeScript Check Strategy

- Type checking is split for faster local feedback:
    - `npm run types:check` (app code)
    - `npm run types:check:tools` (tooling configs)
    - `npm run types:check:all` (full coverage)
- `check` intentionally uses the faster app-focused type check for day-to-day iteration.
- Incremental TS build info is cached in `node_modules/.cache/tsc/`.
- TS config layout:
    - `tsconfig.base.json` for shared compiler options
    - `tsconfig.app.json` for `resources/js`
    - `tsconfig.tools.json` for `vite`/`vitest` config files
- `tsc --noEmit` can appear idle because it is often silent while checking; prefer timing-based diagnosis before assuming a hang.

## Refactor Expectations

- Preserve behavior while refactoring.
- Prefer extraction and naming improvements over adding documentation comments.
- Keep lints/tests green after each refactor slice.
