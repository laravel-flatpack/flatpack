# Package Description and Purpose

## What Flatpack is

Flatpack is a Laravel admin package that renders CRUD-style back-office pages from YAML compositions. Instead of hand-building each admin screen, you define entity behavior in:

- `form.yaml` for create/edit experiences
- `list.yaml` for table/search/filter/index experiences

The package then resolves, normalizes, validates, and renders those definitions through Laravel + Inertia + React.

## Primary Goal

Provide a fast path to production-ready admin tooling while keeping composition declarative and close to the domain model.

## Key Outcomes

- **YAML as source of truth** for page schema and UI behavior.
- **Consistent backend/frontend contract** through generated schema keys and TypeScript types.
- **Extensible action system** for form, list, bulk, and row-level workflows.
- **Normalization pipeline** that supports author-friendly YAML aliases while exposing stable runtime shape.

## Core Building Blocks

- **Compositions**: entity-level YAML definitions for form and list pages.
- **Schema contracts**: JSON schema files under `resources/schema/` for strict shape validation.
- **Normalization**: backend pipes sanitize/merge/normalize before page props are sent.
- **Inertia pages**: React pages (`resources/js/pages/form.tsx` and `resources/js/pages/list.tsx`) consume normalized props.

