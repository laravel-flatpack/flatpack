# List and table columns

List UI columns are defined under `columns` in `list.yaml` (or under a list tab). There is no separate `column.yaml` file.

**Shape:** either an **array** of column definitions (column order follows array order) or an **object** map whose keys are column ids (unless an explicit `id` on the definition overrides the key).

Normative schema: [`resources/schema/list.json`](../../resources/schema/list.json) (`columnDefinition` and related `$defs`).

## Shared properties (list columns)

Most column variants repeat the same optional flags below (see each column type for its **required** keys). Relation and select columns also support `required`, `rules`, and `edit_form_field` / `editFormField` in the full schema.

| Key | Type | Description |
| --- | --- | --- |
| `id` | `string` | Stable column id for sorting keys, filter wiring, and payload paths; when using object-form `columns`, defaults to the YAML map key if omitted. |
| `label` | `string` | Table header label (minimum length 1 in schema). |
| `sortable` | `boolean` | When true, the user can sort the list by this column (subject to backend support for the underlying attribute). |
| `searchable` | `boolean` | When true, global table search can match against this column’s displayed value where implemented. |
| `editable` | `boolean` | When true, inline editing or row-drawer editing may be offered for this cell. |
| `required` | `boolean` | Validation intent when the user edits the cell or row in the drawer. |
| `rules` | `string` \| `string[]` | Laravel validation rules for values produced when editing this column. |
| `detailDrawer` | `boolean` | Per-column override for whether this field participates in row detail / edit drawer flows. |
| `invisible` | `boolean` | When true, the column is hidden from the default visible column set (may still be toggled on if the UI exposes hidden columns). |
| `truncate` | `integer` ≥ 1 | Truncate the rendered cell text after N characters for display density. |
| `edit_form_field` | `editFormFieldDefinition` | Override the form control used when editing this column in the drawer (minimal `{ type: ... }`). |
| `editFormField` | same | CamelCase alias of `edit_form_field`. |

### `editFormFieldDefinition`

Minimal object: `{ type: ... }`. The `type` selects which form field renderer runs in the row drawer:

`text`, `textarea`, `select`, `combobox`, `date-picker`, `date-range-picker`, `time-picker`, `checkbox`, `switch`, `rich-text`, `block-editor`, `table`.

Only these keys are allowed in the schema’s minimal definition; embed extra options only if your normalizer / client parser supports them beyond the strict schema.

The embedded-table schema also allows optional **`span`** on `edit_form_field` / `editFormField` (same values as top-level form fields). The row drawer uses a single-column grid, so spans are accepted but clamped to full width in the UI.

---

## Column types (`columnDefinition`)

The runtime picks a variant from `type`:

1. `select` → `columnSelect`
2. `relation` → `columnRelation`
3. `actions` → `columnActions`
4. `date` / `datetime` → `columnDate`
5. **Text-like** → `columnGeneric` (`text`, `badge`, `status`, or **omit** `type` for plain text display)

### Text-like (`text`, `badge`, `status`, or omitted `type`)

Schema: `columnGeneric`. Used for plain strings, badge chips, and status-style pills.

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | Column header label. |
| `type` | no | `text`, `badge`, or `status`. If omitted, renders as generic text. |
| `format` | no | Token or pattern interpreted by the list formatter (e.g. date/time or numeric formatting). |
| `timezone` | no | IANA or app timezone name for date/time values rendered in this column. |
| `id` | no | Explicit column id. |
| `sortable` | no | Enable header sort for this column. |
| `searchable` | no | Include in global search. |
| `editable` | no | Allow editing via inline or drawer flows. |
| `required` | no | Required when editing this attribute in the drawer. |
| `rules` | no | Validation rules for edited values. |
| `detailDrawer` | no | Drawer participation flag. |
| `invisible` | no | Hide from default visible columns. |
| `truncate` | no | Max displayed characters before ellipsis. |
| `edit_form_field` / `editFormField` | no | Override drawer editor control type. |

### `select`

Schema: `columnSelect`. Renders a label from a fixed option map (badges can carry `status` colors).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `select` |
| `label` | yes | Column header. |
| `options` | yes | `columnOptions`: either an array of `columnOption` objects **or** a map from stored value string → display label string. |
| `id` | no | Column id override. |
| `sortable` | no | Sort by underlying stored value or mapped label, depending on implementation. |
| `searchable` | no | Search participation. |
| `editable` | no | Edit as select in drawer. |
| `required` | no | Required when saving edits. |
| `rules` | no | Laravel rules for the cell value. |
| `detailDrawer` | no | Drawer behavior for this column. |
| `invisible` | no | Hide column by default. |
| `truncate` | no | Truncate display string. |
| `edit_form_field` / `editFormField` | no | Usually redundant if `type: select` matches; override if you need a different editor. |

**`columnOption` object:** `value` and `label` (required). Optional `status` (`success`, `pending`, `warning`, `error`, `info`) for badge coloring, and optional `icon` (e.g. Lucide name).

### `relation`

Schema: `columnRelation`. Resolves display (and often filter options) through an Eloquent relation on the list model.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `relation` |
| `label` | yes | Column header. |
| `relation` | yes | Name of the **relation method** on the list row’s model. |
| `relation_name` **and** `relation_value` **or** `relationName` **and** `relationValue` | yes | Which attributes on the **related** model supply the visible label and the stored id/key (snake_case vs camelCase pairs are equivalent). |
| `options` | no | Optional `columnOptions` to constrain or style labels when not inferring solely from the relation query. |
| `id` | no | Column id. |
| `sortable` | no | Sort when backend can order by related attribute. |
| `searchable` | no | Search across related label. |
| `editable` | no | Edit relation selection in drawer (often combobox). |
| `required` | no | Validation when saving relation ids. |
| `rules` | no | Laravel rules. |
| `detailDrawer` | no | Drawer participation. |
| `invisible` | no | Hide column. |
| `truncate` | no | Truncate related label text. |
| `edit_form_field` / `editFormField` | no | Override editor (e.g. force `combobox` with options). |

### `actions`

Schema: `columnActions`. One or more buttons per row (row-level Flatpack actions or links).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `actions` |
| `label` | yes | Column header (often empty string or “Actions” depending on design). |
| `actions` | yes | Non-empty array of `columnActionButton` definitions. |
| `id` | no | Column id. |
| `sortable` | no | Rarely used for action columns; schema allows it. |
| `searchable` | no | Rarely used. |
| `editable` | no | Not meaningful for most action columns; schema allows. |
| `required` | no | Rarely used; schema allows for parity with other columns. |
| `rules` | no | Rarely used on action columns. |
| `detailDrawer` | no | Rarely used; whether drawer UX ties to this column. |
| `invisible` | no | Hide the whole actions column. |
| `truncate` | no | Not applicable to button cells; included for schema parity. |
| `edit_form_field` / `editFormField` | no | Not used for action columns in typical layouts. |

#### Row action button (`columnActionButton`)

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | Visible button text. |
| `action` **or** `href` | exactly one | `action`: registered row-action key handled by Flatpack. `href`: navigate to URL instead of posting an action. Mutually exclusive. |
| `icon` | no | Lucide (or theme) icon name on the button. |
| `variant` | no | `buttonVariant`: `default`, `outline`, `secondary`, `ghost`, `destructive`, `link`, `primary`. |
| `success_message` | no | Toast message after the action succeeds. |
| `confirm` | no | When true, user must confirm before the action runs. |
| `success_redirect` | no | After success, redirect target keyword or `true` (alias for list index)—see [form-composition.md](./form-composition.md) (`successRedirect`). |

### `date` / `datetime`

Schema: `columnDate`. Formatted date or datetime cells; supports filter ranges when paired with list filters.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `date` (calendar date) or `datetime` (date + time). |
| `label` | yes | Column header. |
| `format` | no | Display format string passed to the formatter (implementation-specific, often Carbon-friendly). |
| `timezone` | no | Timezone for interpreting / displaying stored instants. |
| `id` | no | Column id. |
| `sortable` | no | Sort by chronological value. |
| `searchable` | no | Search participation. |
| `editable` | no | Date/datetime picker in drawer. |
| `required` | no | Required when editing. |
| `rules` | no | Validation rules. |
| `detailDrawer` | no | Drawer participation. |
| `invisible` | no | Hide column. |
| `truncate` | no | Truncate formatted string. |
| `edit_form_field` / `editFormField` | no | Override editor (e.g. `date-picker`). |

---

## Embedded table field columns

Form fields with `type: table` declare **`embeddedTableColumnDefinition`** in [`resources/schema/form.json`](../../resources/schema/form.json). Same authoring idea as list columns, but a **narrower** `type` enum and slightly different option shapes (`selectOptions` vs `columnOptions` naming in schema).

### Allowed `type` values (embedded)

`text`, `select`, `date`, `datetime`, `actions`, `badge`, `status`, `relation`.

Embedded tables do **not** use a separate file: only the `columns` key under the `table` field.

### Embedded column keys

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | Column header in the embedded grid. |
| `id` | no | Stable column id; defaults from map key when using object form. |
| `type` | no | One of the allowed values above; omit for plain text-like cells where schema permits. |
| `options` | for `select` | Static options: `selectOptions` in form schema (array of `{ value, label, … }` or value→label map). |
| `actions` | for `type: actions` | Non-empty array of `fieldTableActionEntry` (toolbar/row actions for embedded tables). |
| `relation`, `relation_name`, `relation_value`, `relationName`, `relationValue` | for `relation` | Same meaning as list relation columns: relation method + label/value columns on the related model. |
| `format` | no | Display format for text or date-like cells. |
| `timezone` | no | Timezone for date/datetime display. |
| `sortable` | no | Client-side or local column sort when enabled in the embedded table. |
| `searchable` | no | Include in embedded table search when implemented. |
| `editable` | no | Editable cell / opens drawer field for that column. |
| `required` | no | Validation when saving row edits. |
| `rules` | no | Laravel rules for the cell value in the drawer. |
| `detailDrawer` | no | Whether this column appears or behaves specially in the row drawer. |
| `invisible` | no | Hide the column in the embedded grid. |
| `truncate` | no | Truncate cell display. |
| `edit_form_field` / `editFormField` | no | Override drawer field control; allowed `type` enum is **smaller** than list `editFormFieldDefinition` (for example no `file-upload` in the embedded schema). |

## List vs embedded table columns

| Aspect | `list.yaml` `columns` | Form `type: table` `columns` |
| --- | --- | --- |
| Schema | `columnDefinition` in `list.json` | `embeddedTableColumnDefinition` in `form.json` |
| `type` | `select`, `relation`, `actions`, `date`, `datetime`, text-like (`text`, `badge`, `status`, omitted) | Subset: no separate first-class types beyond those listed above. |
| Options | `columnOptions` / `columnOption` | `selectOptions` / form option shapes |
| Purpose | Primary entity index table | Nested related rows or ad hoc grids inside a form |

## See also

- [list-composition.md](./list-composition.md) — root and filters
- [form-field-types.md](./form-field-types.md) — `table` field and toolbar actions
- JSON `$defs`: `columnDefinition`, `columnGeneric`, `columnSelect`, `columnRelation`, `columnActions`, `columnDate`, `columnActionButton`, `columnOptions`, `editFormFieldDefinition`, `embeddedTableColumnDefinition`
