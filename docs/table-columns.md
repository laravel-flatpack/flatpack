# Available Table Columns

For the full normative column reference (shared options and each column type), see [YAML reference — List columns](./yaml-reference/list-columns.md). Supported column `type` values are defined in `resources/schema/list.json`.

## Relation-Capable Column Types

| Column type | Relation use-case | Required keys | Optional keys |
| --- | --- | --- | --- |
| `relation` | Render/filter/display values from a related model attribute. | `type: relation`, `relation`, `relation_name` + `relation_value` (or camelCase aliases) | `options`, plus common column options (`sortable`, `searchable`, etc.) |
| `select` | Relation-like display when options are precomputed externally (not relation-resolved by Flatpack). | `type: select`, `options` | Common column options |

For editable embedded table rows (`type: table` form field), relation columns are often combined with `edit_form_field` to control drawer editor rendering.

## Common Column Options

Shared by most or all column types.

| Option | Type | Description |
| --- | --- | --- |
| `id` | `string` | Explicit column id override. |
| `label` | `string` | Column display label. |
| `sortable` | `boolean` | Enables sorting. |
| `searchable` | `boolean` | Enables search participation. |
| `editable` | `boolean` | Enables inline/edit drawer editing where supported. |
| `required` | `boolean` | Validation intent when edited. |
| `rules` | `string \| string[]` | Validation rules for edited value. |
| `detailDrawer` | `boolean` | Controls edit/detail drawer behavior per column. |
| `invisible` | `boolean` | Hides column from default table rendering. |
| `truncate` | `integer` | Truncates display after N chars. |
| `edit_form_field` / `editFormField` | `{ type: ... }` | Override edit drawer field type for that column. |

## Column Definitions

### 1) `text` (default), `badge`, `status`

If `type` is omitted, runtime treats it as text-like.

```yaml
columns:
  title:
    label: Title
    searchable: true

  state:
    type: status
    label: State
```

| Type-specific option | Type | Description |
| --- | --- | --- |
| `type` | `text \| badge \| status` | Optional for text-like columns. |
| `format` | `string` | Optional display formatting token. |
| `timezone` | `string` | Timezone hint for formatted date-like text. |

### 2) `select`

```yaml
columns:
  status:
    type: select
    label: Status
    options:
      - { value: draft, label: Draft }
      - { value: published, label: Published, status: success }
```

| Type-specific option | Type | Description |
| --- | --- | --- |
| `options` | `array \| object` | Required. Array of `{value,label,status?,icon?}` or map (`value: label`). |

### 3) `relation`

```yaml
columns:
  author:
    type: relation
    label: Author
    relation: author
    relation_name: name
    relation_value: id
```

| Type-specific option | Type | Description |
| --- | --- | --- |
| `relation` | `string` | Required relation method name. |
| `relation_name` / `relationName` | `string` | Related label field. |
| `relation_value` / `relationValue` | `string` | Related value/key field. |
| `options` | `array \| object` | Optional explicit options mapping. |

### 4) `actions`

```yaml
columns:
  row_actions:
    type: actions
    label: Actions
    actions:
      - label: Edit
        href: /backend/posts/{id}/edit
      - label: Delete
        action: delete
        variant: destructive
        confirm: true
```

| Type-specific option | Type | Description |
| --- | --- | --- |
| `actions` | `array` | Required. Row action buttons. |

Each row action button supports:

- `label` (required)
- exactly one of `action` or `href`
- `icon`
- `variant`
- `success_message`
- `confirm`
- `success_redirect`

### 5) `date` / `datetime`

```yaml
columns:
  published_at:
    type: datetime
    label: Published
    format: Y-m-d H:i
    timezone: UTC
```

| Type-specific option | Type | Description |
| --- | --- | --- |
| `type` | `date \| datetime` | Date/time display mode. |
| `format` | `string` | Date display format. |
| `timezone` | `string` | Output timezone identifier. |

## Filters (Related to Columns)

`list.yaml` supports a `filters` map keyed by column id:

```yaml
filters:
  status:
    type: select
    label: Status
    multiple: true

  published_at:
    type: date
    mode: from
```

Filter definition variants:

| Filter type | Required | Optional |
| --- | --- | --- |
| `select` | `type` | `label`, `placeholder`, `multiple`, `options` |
| `date` | `type` | `label`, `placeholder`, `mode: exact\|from` |
| overrides | none | `label`, `placeholder` (inherits type from column) |

