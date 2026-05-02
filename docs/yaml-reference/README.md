# YAML composition reference

Normative contracts for Flatpack entity YAML live in the JSON Schema files shipped with the package. This folder expands those contracts into readable reference pages for authors of `form.yaml`, `list.yaml`, and embedded structures (for example `columns` on list pages or on `type: table` form fields).

## Contract vs runtime

- **JSON Schema** describes the allowed shape after YAML is parsed to JSON: allowed keys, types, discriminated unions, and `required` sets. Unknown top-level keys are rejected (`additionalProperties: false` on the root objects).
- **PHP normalizers** may strip non-contract keys in some paths, resolve aliases (for example `relationName` / `relation_name`), or enrich definitions before Inertia. If behavior differs from a literal reading of the schema, prefer the schema for “what may appear in YAML” and tests or normalizer source for edge cases.

When you change top-level properties in [`resources/schema/form.json`](../../resources/schema/form.json) or [`resources/schema/list.json`](../../resources/schema/list.json), regenerate generated schema keys and keep CI green:

```bash
composer run schema:keys:generate
composer run schema:keys
```

## Layout

| Topic | File |
| --- | --- |
| `form.yaml` root, tabs, header actions | [form-composition.md](./form-composition.md) |
| `fields` types and options | [form-field-types.md](./form-field-types.md) |
| `list.yaml` root, tabs, filters, toolbar, bulk, sort | [list-composition.md](./list-composition.md) |
| `columns` (list and differences for embedded tables) | [list-columns.md](./list-columns.md) |
| Dashboard / form `widget` field | [widgets.md](./widgets.md) |

## Terminology

- **List columns** are defined under `columns:` in `list.yaml` (or under a list tab). There is no separate `column.yaml` file in Flatpack.
- **Embedded table columns** belong to a form field `type: table`; their allowed `type` values are a subset of list columns. See [list-columns.md](./list-columns.md#embedded-table-field-columns).

## See also

- [`resources/schema/form.json`](../../resources/schema/form.json) — `form.yaml`
- [`resources/schema/list.json`](../../resources/schema/list.json) — `list.yaml` (including root `widgets` on dashboard entities)
