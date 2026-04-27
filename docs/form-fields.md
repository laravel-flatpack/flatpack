# Available Form Fields

Supported field `type` values are defined in `resources/schema/form.json`.

## Relation-Capable Fields

These field types can directly manage Eloquent relations.

| Field type | Relation use-case | Required relation keys | Optional relation keys |
| --- | --- | --- | --- |
| `combobox` | Pick one or many related records (IDs/keys) via local or remote options. | `relation` (for relation-backed mode) | `relation_name`, `relation_value`, `multiple`, `remote`, `options` |
| `table` | Manage related child rows inline (hydration + sync on submit). | `relation` (for relation-backed mode), `columns` | `relation_value`, `limit`, `table_relation_type`, `row_detail_drawer`, `actions` |

### Relation `combobox` Example

```yaml
fields:
  categories:
    type: combobox
    label: Categories
    relation: categories
    relation_name: name
    relation_value: id
    multiple: true
    remote: true
```

### Relation `table` Example

```yaml
fields:
  items:
    type: table
    label: Items
    relation: items
    relation_value: id
    limit: 100
    columns:
      product_id:
        type: relation
        label: Product
        relation: product
        relation_name: name
        relation_value: id
      quantity:
        label: Quantity
```

## Common Field Options

These are widely shared across field types.

| Option | Type | Description |
| --- | --- | --- |
| `id` | `string` | Explicit field id override. |
| `label` | `string` | Human-readable field label. |
| `helperText` | `string` | Supporting text shown with field. |
| `required` | `boolean` | Marks field as required in UI/validation intent. |
| `rules` | `string \| string[]` | Laravel validation rules. |
| `value` | `unknown` | Initial/default value. |
| `trigger` | `object` | Reactive behavior (`show`, `hide`, `enable`, `disable`, `empty`) for another field based on condition. |

`trigger.condition` supports:

- `checked` / `unchecked`
- `value[<literal>]`

## 1) `text`

```yaml
fields:
  title:
    type: text
    label: Title
    placeholder: Enter title
    required: true
    rules: required|string|max:255
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Input placeholder text. |
| `preset` | `{ field, type }` | Copies/transforms another field value (`exact`, `slug`, `url`, `camel`, `file`). |
| `format` | `slug \| url \| camel \| file` | Input formatting behavior. |

## 2) `textarea`

```yaml
fields:
  excerpt:
    type: textarea
    label: Excerpt
    rows: 4
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Textarea placeholder. |
| `rows` | `integer` | Minimum 1; visible row count. |
| `preset` | `{ field, type }` | Preset transform source mapping. |

## 3) `select`

```yaml
fields:
  status:
    type: select
    label: Status
    options:
      - { value: draft, label: Draft }
      - { value: published, label: Published, status: success }
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Select placeholder. |
| `options` | `array \| object` | Required. Array of `{value,label,status?,icon?}` or map (`value: label`). |

## 4) `combobox`

```yaml
fields:
  author_id:
    type: combobox
    label: Author
    relation: author
    relation_name: name
    relation_value: id
    remote: true
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Combobox placeholder. |
| `options` | `array \| object` | Local options; optional. |
| `multiple` | `boolean` | Multi-select mode. |
| `relation` | `string` | Eloquent relation method for remote options. |
| `relation_name` | `string` | Label attribute on related model. |
| `relation_value` | `string` | Value attribute on related model. |
| `remote` | `boolean` | Enables remote option fetch behavior. |

## 5) `date` (alias)

```yaml
fields:
  publish_date:
    type: date
    label: Publish date
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Date input placeholder. |

Note: runtime normalizes `date` to the date-picker behavior path.

## 6) `date-picker`

```yaml
fields:
  published_at:
    type: date-picker
    label: Published at
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Date picker placeholder. |

## 7) `date-range-picker`

```yaml
fields:
  availability:
    type: date-range-picker
    label: Availability
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Range picker placeholder. |

## 8) `time-picker`

```yaml
fields:
  schedule:
    type: time-picker
    label: Schedule
    dateLabel: Date
    timeLabel: Time
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Base placeholder text. |
| `dateLabel` | `string` | Date part label. |
| `datePlaceholder` | `string` | Date part placeholder. |
| `timeLabel` | `string` | Time part label. |
| `timeDefaultValue` | `string` | Default time value. |

## 9) `checkbox`

```yaml
fields:
  is_featured:
    type: checkbox
    label: Featured
    defaultChecked: false
```

| Option | Type | Description |
| --- | --- | --- |
| `defaultChecked` | `boolean` | Initial checked state. |

## 10) `switch`

```yaml
fields:
  is_active:
    type: switch
    label: Active
    defaultChecked: true
```

| Option | Type | Description |
| --- | --- | --- |
| `defaultChecked` | `boolean` | Initial enabled state. |

## 11) `rich-text`

```yaml
fields:
  body:
    type: rich-text
    label: Body
    showFixedToolbar: true
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Editor placeholder. |
| `showFixedToolbar` | `boolean` | Pin/fix toolbar behavior. |

## 12) `block-editor`

```yaml
fields:
  content:
    type: block-editor
    label: Content
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Editor placeholder. |
| `showFixedToolbar` | `boolean` | Fixed toolbar behavior. |

## 13) `table` (embedded table field)

```yaml
fields:
  items:
    type: table
    label: Items
    relation: items
    relation_value: id
    row_detail_drawer: true
    columns:
      sku:
        label: SKU
      qty:
        label: Qty
```

| Option | Type | Description |
| --- | --- | --- |
| `columns` | `array \| object` | Required. Embedded table columns (same family as list columns). |
| `data` | `array` | Initial rows (usually for non-relation table data). |
| `bulkActions` | `array` | Bulk actions for embedded table rows. |
| `actions` | `array \| object` | Toolbar buttons above table. Highest-precedence toolbar source. |
| `toolbar` | `array \| object` | Alias of `actions` (used when `actions` absent). |
| `toolbar_actions` | `array \| object` | Legacy alias. |
| `toolbarActions` | `array \| object` | Legacy camelCase alias. |
| `reorderable` | `boolean \| string` | Enables row reordering. |
| `relation` | `string` | Parent model relation for relation-backed rows. |
| `relation_value` | `string` | Related row key attribute (default usually `id`). |
| `limit` | `integer` | Max hydrated relation rows. |
| `table_relation_type` | `belongs_to_many \| has_many \| morph_many \| morph_to_many \| has_one \| unknown` | Relation class hint/override. |
| `row_detail_drawer` | `boolean` | Disables/enables row detail drawer open on row click. |

