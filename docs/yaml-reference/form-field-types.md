# `form.yaml` field types

Field definitions live under `fields` (root or inside each tab) as a map of field id → definition. The discriminated union is `fieldDefinition` in [`resources/schema/form.json`](../../resources/schema/form.json).

## Global and common options

Not every key is valid for every `type`. The table below lists keys that appear on **multiple** field definitions; see each type section for its **required** keys and the complete list per type.

| Key | Types / notes | Description |
| --- | --- | --- |
| `type` | all | Discriminator string (`text`, `textarea`, `select`, …). Determines which other keys are allowed. |
| `id` | all | Overrides the map key as the stable field id submitted with the form payload. Use when the YAML key differs from the attribute name you want in values. |
| `label` | all (required in schema) | Primary label shown beside or above the control; minimum length 1. |
| `helperText` | most | Secondary explanatory text (hint), typically below the label or input. |
| `placeholder` | text-like controls where listed below | Grey placeholder text inside an empty input (HTML placeholder). Not present on checkbox, switch, or file-upload in schema. |
| `required` | most | When true, marks the field as required in the UI and feeds validation intent / rules composition. |
| `rules` | most | Laravel validation rules: a single pipe-delimited string or an array of rule strings (`validationRules`). Merged with [type-based defaults](#automatic-validation-rules-for-values) for `values.{id}` on submit. |
| `value` | most | Initial or default value for the field (shape depends on type: string, boolean, array, etc.). |
| `trigger` | most | Conditional UI behavior tied to another field (`fieldTrigger`): show/hide, enable/disable, or clear—see [Triggers](#triggers). |
| `preset` | text, textarea only | Auto-fill this field from another field using `preset` (exact copy or derived slug/url/camel/file)—see [Preset](#preset). |
| `span` | all | Responsive grid width on the form page, in repeaters, and in metadata for embedded-table drawer fields (see [Layout grid](#layout-grid)). Named: `full`, `half`, `two_thirds`, `third`, `quarter`. Aliases: `1/2`, `2/3`, `1/3`, `1/4` (normalized server-side). |

**Checkbox and switch** do not declare `placeholder`. **File upload** does not declare `placeholder` in the schema.

### Automatic validation rules for `values`

On form submit, Flatpack builds Laravel rules for each `values.{fieldId}` from field **`type`**, **`required`**, and optional relation/list helpers, then merges optional YAML **`rules`**.

- **`rich-text`** and **`block-editor`** default to **`array`** (the client sends a Plate/Slate **JSON array of nodes**). Do not treat these as plain strings in passthrough rules.
- **`text`** and **`textarea`** default to **`string`**.

See [Available Form Fields — Built-in Laravel validation](../form-fields.md#built-in-laravel-validation-for-submitted-values) for a broader summary.

### Layout grid (`span`)

The form page lays fields out in a responsive CSS grid: **1 / 2 / 4 / 5** columns at default / `md` / `lg` / `2xl` breakpoints. When `span` is omitted, each field spans the full row (same as today’s stacked layout). Repeater item bodies use a narrower grid (**1 / 2 / 3** at default / `md` / `2xl`). Row-edit drawers use a single column; `span` is accepted on `edit_form_field` but always renders full width there.

### Triggers (`trigger` / `fieldTrigger`)

| Key | Required | Description |
| --- | --- | --- |
| `action` | yes | What to do to the *target* field: `show`, `hide`, `enable`, `disable`, or `empty` (clear). |
| `field` | yes | Id of the other field to watch (non-empty string). |
| `condition` | yes | When the action runs: `checked` / `unchecked` for boolean fields, or `value[literal]` to match a specific value. |

### Preset (`preset`) — `text` and `textarea` only

| Key | Required | Description |
| --- | --- | --- |
| `field` | yes | Id of the source field whose value is read when the preset runs. |
| `type` | yes | How to transform the copy: `exact` (copy as-is), or derive `slug`, `url`, `camel`, or `file` from the source. |

---

## `text`

Single-line text input.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `text`. |
| `label` | yes | Display label (min length 1). |
| `id` | no | Explicit field id (defaults to YAML map key). |
| `helperText` | no | Hint text under the control. |
| `placeholder` | no | Placeholder when empty. |
| `required` | no | Required marker + validation wiring. |
| `rules` | no | Laravel validation rules for this value. |
| `value` | no | Default string (or coerced) value. |
| `preset` | no | Copy or derive from another field; see [Preset](#preset). |
| `format` | no | Optional display/slug behavior: `slug`, `url`, `camel`, or `file` (in addition to free text). |
| `trigger` | no | Conditional rules; see [Triggers](#triggers). |

---

## `textarea`

Multi-line text input.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `textarea`. |
| `label` | yes | Display label. |
| `rows` | no | Number of visible text lines (integer ≥ 1). |
| `preset` | no | Same as `text` (copy or transform from another field). |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder when empty. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default value. |
| `trigger` | no | Conditional rules. |

---

## `select`

Dropdown with a fixed option list (static; not the same as relation-search combobox).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `select`. |
| `label` | yes | Display label. |
| `options` | yes | `selectOptions`: array of `{ value, label, status?, icon? }` **or** object map of value string → display label string. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder for the closed select. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Selected value(s) default. |
| `trigger` | no | Conditional rules. |

---

## `combobox`

Searchable select; optional static `options` and/or Eloquent `relation` for async or loaded options.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `combobox`. |
| `label` | yes | Display label. |
| `options` | no | Static options (same `selectOptions` shape) when not relying solely on the relation API. |
| `multiple` | no | When true, allows many related values (chips / multi-select). |
| `relation` | no | Eloquent **relation method name** on the form model; enables loading options (and labels) from related records. Preferred over legacy `type: relation` fields. |
| `relation_name` | no | Attribute on the **related** model used as the option label (e.g. `name`, `title`). |
| `relation_value` | no | Attribute on the **related** model used as the stored value (commonly `id`). |
| `remote` | no | When true, options are loaded remotely (search/typeahead) instead of only from static `options`. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder in the input. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default selected id(s) or value(s). |
| `trigger` | no | Conditional rules. |

---

## `date` (legacy)

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `date`. **Legacy alias**; normalized toward date-picker behavior in the stack. Prefer `date-picker` for new YAML. |
| `label` | yes | Display label. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules (often `date`). |
| `value` | no | Default date value. |
| `trigger` | no | Conditional rules. |

---

## `date-picker`

Single calendar date.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `date-picker`. |
| `label` | yes | Display label. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder in the date input. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default selected date. |
| `trigger` | no | Conditional rules. |

---

## `date-range-picker`

Start and end dates as one field value.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `date-range-picker`. |
| `label` | yes | Display label. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder for the range control. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default range value (structure as emitted by the UI/normalizer). |
| `trigger` | no | Conditional rules. |

---

## `time-picker`

Combined date + time selection (labels can split date vs time parts in the UI).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `time-picker`. |
| `label` | yes | Overall field label. |
| `dateLabel` | no | Label for the date portion of the control. |
| `datePlaceholder` | no | Placeholder for the date portion. |
| `timeLabel` | no | Label for the time portion of the control. |
| `timeDefaultValue` | no | Default time string when appropriate. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | General placeholder if used by the implementation. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default date/time value. |
| `trigger` | no | Conditional rules. |

---

## `checkbox`

Boolean checkbox.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `checkbox`. |
| `label` | yes | Label shown next to the box. |
| `defaultChecked` | no | Initial checked state when no `value` is provided. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text (often below the row). |
| `required` | no | Required when true (e.g. must be checked). |
| `rules` | no | Validation rules. |
| `value` | no | Controlled default boolean. |
| `trigger` | no | Conditional rules (often paired with other fields via `checked` / `unchecked`). |

---

## `switch`

Toggle switch (same value semantics as checkbox, different chrome).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `switch`. |
| `label` | yes | Display label for the switch row. |
| `defaultChecked` | no | Initial on/off state when no `value` is provided. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules. |
| `value` | no | Default boolean. |
| `trigger` | no | Conditional rules. |

---

## `rich-text`

WYSIWYG / Plate-style rich text editor.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `rich-text`. |
| `label` | yes | Display label. |
| `toolbar` | no | When true, shows the pinned formatting toolbar (text styles, **H1–H3** headings, lists, indent, table/toggle, media); when false/omitted, uses the default editor layout. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder in the editable area. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules (merged with defaults; base rule is **`array`**, not `string`). |
| `value` | no | Initial Plate document: **JSON array of Slate/Plate nodes** (same structure as the submitted `values.{id}` payload). |
| `trigger` | no | Conditional rules. |

---

## `block-editor`

Block-based editor (structured content).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `block-editor`. |
| `label` | yes | Display label. |
| `toolbar` | no | Same as `rich-text`: pin the formatting toolbar (includes **H1–H3** and other controls) for consistent discovery. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text. |
| `placeholder` | no | Placeholder for empty document. |
| `required` | no | Required when true. |
| `rules` | no | Validation rules (merged with defaults; base rule is **`array`**, not `string`). |
| `value` | no | Initial document: **JSON array of Slate/Plate nodes** (same as `rich-text`; structured blocks, not HTML). |
| `trigger` | no | Conditional rules. |

---

## `file-upload`

Upload files either as **related records** (`relation` mode, e.g. media library) or as **URL strings** stored on attributes (`url` mode).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `file-upload`. |
| `label` | yes | Display label. |
| `mode` | yes | `relation`: persist via an Eloquent relation (attachments). `url`: store file URL(s) in model attribute(s). |
| `relation` | **if** `mode: relation` | Relation method name on the parent model that receives uploaded files. |
| `target_column` | **if** `mode: url` | Attribute name on the model where the URL string (or JSON array of URLs) is stored. |
| `multiple` | no | Allow more than one file in one field. |
| `max_files` | no | Upper bound on number of files (integer ≥ 1). |
| `max_size_kb` | no | Max size per file in kilobytes (integer ≥ 1). |
| `accept` | no | Browser `accept` filter: one MIME/extension string or an array of allowed patterns. |
| `directory` | no | Optional subdirectory under the disk for stored files. |
| `disk` | no | Laravel filesystem disk name (non-empty string). |
| `visibility` | no | Stored object visibility: `public` or `private`. |
| `collection` | no | Optional Spatie-style **collection** name for relation mode grouping. |
| `persist_as` | no | When `mode: url` and `multiple`: store as a single `string` or encoded `json` in `target_column`. |
| `id` | no | Explicit field id. |
| `helperText` | no | Hint text (e.g. allowed types). |
| `required` | no | Required when true (at least one file). |
| `rules` | no | Validation rules (e.g. `image`, `max`). |
| `value` | no | Existing attachment ids or URLs for edit forms. |
| `trigger` | no | Conditional rules. |

---

## `table` (embedded table)

**Required:** `type`, `label`, `columns`.

Inline table for child rows: optional **relation-backed** sync (`relation`) or **direct model** rows (`model`), or neither for custom flows. Column definitions use `embeddedTableColumnDefinition` (see [list-columns.md](./list-columns.md#embedded-table-field-columns)).

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `table`. |
| `label` | yes | Section label for the embedded table. |
| `columns` | yes | Non-empty array **or** map of column id → `embeddedTableColumnDefinition` (same general idea as list columns; see list-columns doc). |
| `data` | no | Row payload array (often hydrated server-side for edit screens). |
| `bulkActions` | no | Bulk-action definitions for selected embedded rows (array shape in schema; wiring depends on host). |
| `actions` | no | Primary toolbar actions (`fieldTableActions`: ordered array or map). If both `actions` and `toolbar` exist, **`actions` wins**. |
| `toolbar` | no | Alias of `actions`; ignored when `actions` is set. |
| `toolbar_actions` | no | Legacy snake_case toolbar; used only if `actions` and `toolbar` are absent. |
| `toolbarActions` | no | Legacy camelCase toolbar; lowest precedence of the four. |
| `reorderable` | no | `true` for default ordering, or a **string** column name used as the manual sort key. |
| `default_sort` | no | Initial sort: `{ key, direction }` with `direction` `asc` or `desc`. |
| `pagination` | no | Force pagination UI on (`true`) or off (`false`) for the embedded grid. |
| `showColumnsVisibility` | no | Show or hide the “column visibility” dropdown in the embedded toolbar. |
| `row_detail_drawer` | no | When `false`, clicking a row does not open the row detail drawer; toolbar `create` / `add` may still use the drawer for new rows. |
| `relation` | * | Relation method on the parent for hydrate + save of related rows. **Mutually exclusive** with `model` in schema branches. |
| `model` | * | Related model class for row persistence paths that bypass pure relation sync. **Mutually exclusive** with `relation` in schema branches. |
| `relation_value` | no | Primary key attribute on the related model for row identity (default `id`). |
| `limit` | no | Maximum related rows to load (also capped by package config). |
| `table_relation_type` | no | Hint for relation kind: `belongs_to_many`, `has_many`, `morph_many`, `morph_to_many`, `has_one`, or `unknown` (for detection overrides). |
| `id` | no | Explicit field id for the whole table field. |
| `helperText` | no | Hint text above the table. |
| `required` | no | Whether the table / rows are required for validation. |
| `rules` | no | Validation rules for nested row data. |
| `value` | no | Serialized row state for the field. |
| `trigger` | no | Conditional visibility of the whole table block. |

You may declare **neither** `relation` nor `model` when using custom persistence or host-specific hooks.

**Toolbar action entry (`fieldTableActionEntry`):** requires `label` and `action`. Built-ins `create` and `add` open a new draft row in the row detail drawer. Optional: `id`, `icon`, `variant` (`default`, `destructive`, `outline`, `secondary`, `ghost`, `link`, `primary`).

---

## `widget` (read-only widget in a form)

Renders a dashboard-style widget (metric, card, chart, table, …) inside the form layout.

**Required:** `type`, `label`, `widget`.

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | Must be `widget`. |
| `label` | yes | Label for the surrounding form section / chrome. |
| `widget` | yes | Object with **at least one** key: **widget id** → full `widgetDefinition` (same union as dashboard `list.yaml` widgets). Often exactly one entry. |
| `helperText` | no | Hint text below the section title. |
| `trigger` | no | Show/hide the entire widget block based on another field. |
| `id` | no | Explicit field id. |

Exact table-widget sub-keys follow [`resources/schema/form.json`](../../resources/schema/form.json) `$defs/widgetDefinition` (minor differences vs `list.json` for some table keys). See [widgets.md](./widgets.md).

---

## `repeater`

Repeating groups of sub-fields stored as an **array of objects** in the form payload.

**Required:** `type`, `label`.

Exactly **one** of: **`groups`**, **`form`**, or **`fields`** (inline map)—see schema `oneOf`.

When **`groups`** is an inlined map or array (not a path string), the form UI renders a **block type** control per row and the nested fields for the selected template. Rows store the chosen template id under **`groupKeyFrom`** (default **`_group`**). Changing the block type replaces that row’s payload with only the new group key (previous field values are cleared).

### `repeaterGroupDefinition` (each group template)

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | Human-readable name in the group picker. |
| `fields` | yes | Map of nested field id → `fieldDefinition` for that template. |
| `key` | no | Stable key stored on each item (when omitted for map-defined groups, the YAML map key may be used). |
| `icon` | no | Lucide icon name for the group option in the UI. |

### Other repeater keys

| Key | Required | Description |
| --- | --- | --- |
| `groups` | * | One of three modes: path string to YAML file, array of group defs, or map of group id → `repeaterGroupDefinition`. |
| `form` | * | Path string (under entity composition dir) to a YAML fragment **only** defining item fields. Mutually exclusive with `groups` and inline `fields`. |
| `fields` | * | Inline map of item field id → `fieldDefinition` for a single repeating template. Mutually exclusive with `groups` and `form`. |
| `id` | no | Explicit field id for the repeater. |
| `helperText` | no | Hint text for the whole repeater. |
| `required` | no | Validation intent for the array / items. |
| `rules` | no | Laravel rules applied to the repeater value. |
| `value` | no | Initial array of item objects. |
| `prompt` | no | Button label for adding an item (default: “Add new item”). |
| `displayMode` | no | `accordion` (collapsible rows) or `builder` (alternate stacked editor). Default: `accordion`. |
| `itemsExpanded` | no | In accordion mode, whether new/existing items start expanded (default **true**). |
| `titleFrom` | no | Attribute key on each item used as the row title; set to **`false`** (only with `minItems` and `maxItems` both `1`) to hide the per-row title bar. |
| `minItems` | no | Minimum item count; may render empty starter rows (e.g. `minItems: 1` shows one row). |
| `maxItems` | no | Maximum items allowed (integer ≥ 1). |
| `groupKeyFrom` | no | Attribute name where the selected group key is stored on each item (default `_group`). |
| `showReorder` | no | Show drag/reorder controls (default **true**). |
| `showDuplicate` | no | Show duplicate-row control (default **true**). |
| `trigger` | no | Conditional visibility of the repeater block. |

## See also

- [form-composition.md](./form-composition.md) — root layout and actions
- [list-columns.md](./list-columns.md) — embedded table columns
- [widgets.md](./widgets.md) — widget definitions
- JSON `$defs`: `fieldDefinition`, `fieldTable`, `fieldRepeater`, `embeddedTableColumnDefinition`, `widgetDefinition` in [`resources/schema/form.json`](../../resources/schema/form.json)
