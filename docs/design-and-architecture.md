# Design and Architecture

## High-Level Flow

1. Flatpack loads entity YAML composition (`form.yaml` or `list.yaml`).
2. Composition is validated/sanitized against JSON schema contracts.
3. Normalization pipes convert authoring shapes into runtime-safe structures.
4. Controllers build page props and return Inertia responses.
5. React hooks/components render data tables and form fields from normalized schema.

## Runtime Architecture

### Backend

- **Controllers**
  - `FormController`: create/edit/submit actions and form schema normalization.
  - `ListController`: list query resolution (search, filters, sorting, tabs, pagination).
- **Composition loaders**
  - Read YAML and expose composition data to controllers.
- **Normalization pipelines**
  - Example: `MergeListTabsIntoColumnsPipe` merges `tabs.*.columns` into one table column set and emits `tab_panels` metadata.
- **Response layer**
  - `FlatpackResponse` returns Inertia pages (or JSON mode where supported) with consistent payload shape.

### Frontend

- **Inertia pages**
  - `resources/js/pages/form.tsx`
  - `resources/js/pages/list.tsx`
- **Hooks**
  - `useFlatpackForm` and `useFlatpackList` map page props + schema into UI behavior.
- **Typed contracts**
  - Page props in `resources/js/types/pages/flatpack.ts`
  - Normalized schema + composition helper types in `resources/js/types/form-composition.ts` and `resources/js/types/list-composition.ts` (what Inertia sends matches normalizer output, not authoring-only YAML shortcuts)

## Design Principles

- **Schema-first contracts**: JSON schemas in `resources/schema/` define supported keys.
- **Normalized runtime shape**: YAML aliases and ergonomic authoring forms are converted to predictable runtime values.
- **Cross-layer parity**: PHP schema keys and TypeScript contracts are kept aligned.
- **Composable UI primitives**: table/form rendering is driven by schema, not hardcoded per-entity components.

## Tabs Behavior (Important)

- **Form tabs**: authoring may split `tabs.*.fields` from top-level `fields`; normalizers merge these for the runtime schema (`fields` + `tab_panels`) before Inertia.
- **List tabs**: tab columns merge into one column registry server-side; active tab selects visible columns plus optional scope/filter/bulk-action overrides.

