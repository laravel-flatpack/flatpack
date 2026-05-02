# Flatpack Package Docs

This folder documents the package internals and YAML composition contracts used by Flatpack.

## Contents

- [Composer and backend tooling](./tooling.md) (Pest, checks, schema keys, code coverage)
- [Package Description and Purpose](./package-description.md)
- [Design and Architecture](./design-and-architecture.md)
- [YAML reference](./yaml-reference/README.md) — normative `form.yaml` / `list.yaml` keys, fields, columns, widgets
- [Form Page YAML (Page-Level) Props](./form-page-props.md)
- [List Page YAML (Page-Level) Props](./list-page-props.md)
- [Available Form Fields](./form-fields.md)
- [Available Table Columns](./table-columns.md)

Relation management references:

- Form relation fields: [Available Form Fields](./form-fields.md#relation-capable-fields)
- Relation-aware columns: [Available Table Columns](./table-columns.md#relation-capable-column-types)

## Scope

These docs describe the package behavior reflected in:

- `resources/schema/form.json`
- `resources/schema/list.json`
- `resources/js/types/form-composition.ts`
- `resources/js/types/list-composition.ts`
- `resources/js/types/pages/flatpack.ts`

