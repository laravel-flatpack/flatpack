# Embedded form table (`type: table`)

Inline grids on entity **`form.yaml`** `fields`. Authoritative shape: `vendor/flatpack/flatpack/resources/schema/form.json` → `$defs.fieldTable`, and https://laravel-flatpack.com/reference.

## Minimal (local rows)

```yaml
fields:
  lines:
    type: table
    label: Line items
    columns:
      - { id: sku, label: SKU, type: text, editable: true }
      - { id: qty, label: Qty, type: text, editable: true }
```

`columns` matches **list** column shapes (array of defs with `id`, or id → def map).
Per column, optional **`edit_form_field`** provides a full form-field definition for row-drawer rendering. If omitted, strict column-type defaults are used.

## Relation-backed example

```yaml
fields:
  tags:
    type: table
    label: Tags
    relation: tags
    relation_value: id
    actions:
      add_tag:
        label: Add tag
        action: add
        icon: plus
    columns:
      - { id: name, label: Name, type: text }
```

- **`relation` / `relation_value`:** identity for sync; optional **`limit`**.
- **≥1 column** required or the field is dropped in normalization.
- **`table_relation_type`:** Eloquent relation class for this field (`has_many`, `belongs_to_many`, …), usually inferred from the form’s `model`. Authors may set it in YAML to override.

## Default actions (relation tables)

When **`relation`** is set and the author **omits** the corresponding YAML keys, sensible defaults are added:

- **Toolbar** (unless `actions`, `toolbar`, `toolbar_actions`, or `toolbarActions` is present, including empty `actions: []`): Create for has-many style relations; Create + Attach for belongs-to-many.
- **Bulk** (unless `bulk_actions` / `bulkActions` is present): Delete selected (with confirm).
- **Per-row** (unless a column with **`type: actions`** already exists): trailing Actions column (Edit, Remove; Detach for BTM).

## Toolbar

- **Key precedence (first wins):** `actions` → `toolbar` → `toolbar_actions` / `toolbarActions`.
- **Shape:** non-empty **array** of button objects, or **object** keyed by stable id → button. Per button: **`label`**, **`action`** (string); optional `id`, `icon`, `variant`.
- Custom `action` values need app handling via embedded table toolbar callbacks.

**Relation + toolbar + unsaved parent:** the toolbar is disabled until the parent form is saved.

## Row detail drawer

- Default: **row click** opens the full-row drawer. **`row_detail_drawer: false`** turns off click-to-open; toolbar **`create` / `add`** still open the draft drawer for new rows.
- Optional alias **`openDetailDrawerOnRowClick`** (camelCase wins if both YAML keys are present).
- **Relation columns** (`type: relation`) load options from the embedded-table relation-options endpoint when on a form page.
- Use **`edit_form_field`** on a column to override drawer field type.

## Other keys

- **`bulkActions` / `bulk_actions`**, **`reorderable`**, optional **`data`** (initial inline rows).
