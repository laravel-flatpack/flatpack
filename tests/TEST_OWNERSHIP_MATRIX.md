# Flatpack Test Ownership Matrix

Use one canonical test layer per behavior to avoid duplicated assertions.

## Ownership Rules

- `Schema shape / key validity`: `tests/Unit/CompositionSchemaContractTest.php`
- `Parser/runtime coercion`: `resources/js/lib/*.test.ts`
- `HTTP endpoint semantics`: `tests/Feature/*`
- `UI interaction behavior`: `resources/js/components/**/*.test.tsx`

## Avoid Duplication

- Do not reassert full parser normalization payloads in feature tests unless the endpoint contract depends on them.
- Keep parser details (alias support, option coercion, action normalization) in parser unit tests.
- Keep feature tests focused on externally observable behavior: status codes, response envelopes, persistence effects, redirects.

## Current Canonical Coverage

- List schema contract: `tests/Unit/CompositionSchemaContractTest.php`
- List parser normalization: `resources/js/lib/list-schema.test.ts`
- Drawer mapper coercion: `resources/js/lib/data-table-row-drawer-field-mapper.test.ts`
- Relation options endpoint semantics: `tests/Feature/FormSaveTest.php`
