# Available Form Fields

For the full normative field reference (every key and type-level option), see [YAML reference — Form field types](./yaml-reference/form-field-types.md). Supported field `type` values are defined in `resources/schema/form.json`.

## Relation-Capable Fields

These field types can directly manage Eloquent relations.

| Field type | Relation use-case | Required relation keys | Optional relation keys |
| --- | --- | --- | --- |
| `combobox` | Pick one or many related records (IDs/keys) via local or remote options. | `relation` (for relation-backed mode) | `relation_name`, `relation_value`, `multiple`, `remote`, `options` |
| `file-upload` | Persist uploaded files as related records (for media/attachments). | `mode: relation`, `relation` | `collection`, `disk`, `directory`, `accept`, `max_size_kb`, `max_files`, `multiple` |
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
| `span` | `string` | Optional grid span: `full`, `half`, `two_thirds`, `third`, `quarter`, or aliases `1/2`, `2/3`, `1/3`, `1/4`. See [YAML reference — field types](./yaml-reference/form-field-types.md#layout-grid-span). |
| `fieldset` | `string \| object` | Optional section grouping for consecutive fields (`label`, optional `icon`, optional `variant`, optional `collapsed`). See [YAML reference — Fieldset grouping](./yaml-reference/form-field-types.md#fieldset-grouping). |

### Built-in Laravel validation for submitted values

For actions with `submit: true`, Flatpack composes Laravel rules for each `values.{fieldId}` from the merged form schema (`fields` + tab `fields`). Base rules depend on field **`type`** and **`required`** (and relation helpers where applicable); optional YAML **`rules`** are **merged** on top.

Notable defaults:

| Field type | Typical built-in rule (after `required` / `nullable`) |
| --- | --- |
| `rich-text`, `block-editor` | **`array`** — the browser submits a **Plate/Slate document** as a JSON **array of nodes**, not a scalar string. |
| `text`, `textarea` | `string` |
| `repeater`, `table`, `date-range-picker`, … | `array` (where applicable) |

Use an Eloquent **`array`** or **`json`** cast (or a JSON column) for model attributes that store `rich-text` / `block-editor` payloads.

If you add passthrough **`rules`** in YAML, avoid conflicting overrides—for example, do **not** append `string` to editor fields.

`trigger.condition` supports:

- `checked` / `unchecked`
- `value[<literal>]`

## Form Header Actions: `submit`

Form page header actions (YAML top-level `actions`) can either submit form values or run as direct record actions.

### `submit` behavior

- `submit: true` -> posts to `flatpack.entities.form.submit` and sends current `values`.
- `submit: false` (or omitted) -> runs `flatpack.entities.row-action` without sending form `values`.
- Default is `false`.

Direct record actions (`submit: false`) require edit mode (an existing record). Use this for command-like actions (delete, resend email, regenerate key, sync) that should not validate or submit unsaved form values.

### Example

```yaml
actions:
  save:
    label: Save
    action: save
    submit: true
    variant: primary
    enabled_if:
      all:
        - form.dirty: true
      message: No changes to save

  delete:
    label: Delete
    action: delete
    variant: destructive
    confirm: true
    visible_if:
      all:
        - form.mode_in: [edit]
```

### `enabled_if` / `visible_if` predicates

Actions stay **disabled** (`enabled_if`) or **hidden** (`visible_if`) until every clause in `all` passes and at least one clause in `any` passes (when present). List views support selection/search predicates; **form pages** also resolve predicates against live **`values`** (same keys as form fields).

**Built-in (non-field) predicates**

| Predicate | Meaning |
| --- | --- |
| `form.dirty` | Matches current dirty state (`true` / `false`). |
| `form.mode_in` | Current page mode is one of `create` or `edit`. |
| `list.selection.min` | At least N rows selected (lists/bulk). |
| `list.search_present` | Search box empty vs non-empty (lists). |
| `list.filters_applied` | Filters applied vs none (lists). |

**Field predicates** (form pages only; use top-level attribute names from `values`, not dotted paths)

| Predicate | Meaning |
| --- | --- |
| `form.field_eq` | Field value **deep-equals** `value` (use `value: null` only when you need exactly JSON null). |
| `form.field_in` | Scalar field: value is in `values`. Multi-value field (array): **any** item is in `values`. |
| `form.field_truthy` | Field satisfies truthiness (see below). **Not** the same as “not null”. |
| `form.field_present` | Value is neither `null` nor `undefined` (missing key counts as undefined). |
| `form.field_null` | Value is `null`, `undefined`, or the key is absent—use this for optional clears instead of `form.field_eq` + `null` unless you must distinguish missing vs null. |

**Truthy rule for `form.field_truthy`:** `null`, `undefined`, `false`, `''`, `[]`, and `0` are falsy; non-empty trimmed strings, non-zero numbers, `true`, non-empty arrays/objects are truthy.

```yaml
enabled_if:
  all:
    - form.field_eq:
        field: status
        value: draft
    - form.field_present:
        field: reviewer_id
visible_if:
  any:
    - form.field_null:
        field: archived_at
```

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
    toolbar: true
```

| Option | Type | Description |
| --- | --- | --- |
| `placeholder` | `string` | Editor placeholder. |
| `toolbar` | `boolean` | When true, shows the pinned formatting toolbar; when false or omitted, uses the default editor chrome. |

**Persistence:** the submitted value is a JSON **array** (Plate document). Map it to a cast such as `'body' => 'array'` or `'body' => 'json'` on your model (see [built-in validation](#built-in-laravel-validation-for-submitted-values)).

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
| `toolbar` | `boolean` | Same as `rich-text`: pinned formatting toolbar when true. |

**Persistence:** same as `rich-text` — submitted value is a JSON **array** (Plate document); use an **`array`** / **`json`** model cast or JSON column. See [built-in validation](#built-in-laravel-validation-for-submitted-values).

## 13) `file-upload`

```yaml
fields:
  avatar:
    type: file-upload
    label: Avatar
    mode: url
    target_column: avatar_url
    disk: files
    directory: users/avatars
    accept: [image/jpeg, image/png]
    max_size_kb: 2048
```

```yaml
fields:
  gallery:
    type: file-upload
    label: Gallery
    mode: relation
    relation: media
    collection: gallery
    multiple: true
    max_files: 10
    accept: [image/*]
```

| Option | Type | Description |
| --- | --- | --- |
| `mode` | `relation \| url` | Required. Persistence mode: related records (`relation`) or model URL column(s) (`url`). |
| `multiple` | `boolean` | Enables multiple uploaded files. |
| `relation` | `string` | Required in `relation` mode: relation method on parent model. |
| `collection` | `string` | Optional grouping label for relation-backed rows. |
| `target_column` | `string` | Required in `url` mode: attribute that stores URL value(s). |
| `persist_as` | `string \| json` | For `url` + `multiple`; `json` stores array payload, `string` stores comma-separated URLs. |
| `disk` | `string` | Storage disk override. |
| `directory` | `string` | Optional upload directory within disk. |
| `visibility` | `public \| private` | Stored file visibility preference. |
| `accept` | `string \| string[]` | Accepted MIME filters. |
| `max_size_kb` | `integer` | Max file size per upload. |
| `max_files` | `integer` | Max file count for multi mode. |

## 14) `table` (embedded table field)

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

### `table.reorderable`

`reorderable` on table fields enables drag-and-drop row ordering inside form pages.

- `reorderable: true` uses the default `sort_order` column.
- `reorderable: sorting_order` uses the provided custom column.
- No trait is required on your models; Flatpack handles reorder persistence.

Example:

```yaml
fields:
  comments:
    type: table
    relation: comments
    reorderable: true
    columns:
      body:
        type: text
        label: Body
```

Persistence requirements:

- The configured reorder column must exist on the related model table.
- Existing rows should already have contiguous values (`1..n`) in that column before enabling reordering.

## 15) `repeater`

A repeater stores **JSON/array-shaped form data**: each item is an object whose keys match nested field ids. Values persist on the parent attribute your YAML field id maps to (typically a JSON cast or array column).

You must supply **exactly one** of:

- **`fields`** — Inline nested field map (same shapes as top-level `fields` in `resources/schema/form.json`).
- **`form`** — Non-empty **string**: relative path from the entity’s composition directory to a YAML fragment that defines the item fields (only a path is allowed; inline `{ fields: ... }` here is invalid).
- **`groups`** — Grouped templates: a path string, ordered list, or keyed map of group definitions (each group has its own `label` and `fields` map). Mutually exclusive with `fields` and `form`.

Nested `fields` support the same `type` values as the rest of the form, including **nested `repeater`** fields.

### Example (inline `fields`)

```yaml
fields:
  line_items:
    type: repeater
    label: Line items
    minItems: 1
    maxItems: 20
    titleFrom: name
    displayMode: accordion
    itemsExpanded: true
    fields:
      name:
        type: text
        label: Name
        required: true
      quantity:
        type: text
        label: Qty
      price:
        type: text
        label: Price
```

### Example (`form` fragment path)

```yaml
fields:
  steps:
    type: repeater
    label: Steps
    titleFrom: title
    form: step_item.yaml
```

Place `step_item.yaml` beside that entity’s `form.yaml` (under `flatpack/{entity}/` by default). The fragment carries the **same nested field map** you would have put under `fields` on the repeater when inlining.

**`step_item.yaml`** (fragment referenced by `form`):

```yaml
fields:
  title:
    type: text
    label: Step title
  notes:
    type: textarea
    label: Notes
    rows: 4
```

Each key (`title`, `notes`, …) becomes an attribute on each repeater item. Your loader / normalizer should merge this file into the repeater’s item `fields` before the UI runs (same end shape as the inline example above).

### Example (group mode)

```yaml
fields:
  blocks:
    type: repeater
    label: Blocks
    groupKeyFrom: _group
    groups:
      hero:
        label: Hero
        fields:
          title:
            type: text
            label: Title
      quote:
        label: Quote
        fields:
          body:
            type: textarea
            label: Quote
```

| Option | Type | Description |
| --- | --- | --- |
| `fields` | `object` | Per-item field map. Exclusive with `form` and `groups`. |
| `form` | `string` | Path to YAML fragment (string only). Exclusive with `fields` and `groups`. |
| `groups` | `string \| array \| object` | Group definitions or path to them. Exclusive with `fields` and `form`. |
| `groupKeyFrom` | `string` | Attribute on each saved item that stores the selected group key. Default: `_group`. |
| `displayMode` | `accordion \| builder` | Row UI: collapsible rows vs stacked builder layout. Default: `accordion`. |
| `itemsExpanded` | `boolean` | When `displayMode` is `accordion`, whether rows start expanded. Default: `true`. |
| `titleFrom` | `string \| false` | Item attribute used as the collapsed row title. With `minItems: 1` and `maxItems: 1`, set to `false` to hide the per-row title bar. |
| `minItems` | `integer` | Minimum items; `minItems: 1` can show one initial row. |
| `maxItems` | `integer` | Maximum items. |
| `prompt` | `string` | Label for the control that adds a new item. Default: Add new item. |
| `showReorder` | `boolean` | When `true` and there are at least two items (and not a fixed single row), each row shows a drag handle for vertical reordering. Default: `true`. |
| `showDuplicate` | `boolean` | Show duplicate-row control. Default: `true`. |

### Fixed single row (`minItems` and `maxItems` both `1`)

When only one row is allowed, the UI omits **Add**, **reorder**, **duplicate**, and **delete** for that repeater. Set **`titleFrom: false`** if you also want to hide the accordion/builder row title strip (nested fields only).

## Toolbar field (`type: toolbar`)

Renders the same **header-style actions** as the top-level form `actions` block (save, record handlers, links). Use it in **main fields**, **tabs**, or **`sidebar.fields`** for inline toolbars. The nested YAML **`actions`** map matches **`form.yaml` root `actions`** (same map shape as top-level form actions): keyed by stable id, each value uses the same keys as a header row (`label`, `action` or `href`, `icon`, `submit`, `confirm`, `variant`, `enabled_if`, `visible_if`, `shortcut`, …).

- **`type`** — Optional when an **`actions`** block is present; the normalizer infers `toolbar`.
- **`align`** — `left`, `right`, `center`, `start`, `end`, or **`spaced`** (equal space between buttons, `justify-between`). Default **`right`**.
- **Persistence** — Toolbar rows are **not** submitted as `values.{fieldId}`; they trigger the same submit / row-action flow as the page header.
- **Handlers** — For **top-level** form actions, `action` names must exist under `config('flatpack.actions')`; unconfigured rows are omitted. For **`type: toolbar`** inline rows, unconfigured handlers are **still sent** to the UI with `handler_missing` so buttons render **disabled** (register the handler to enable them).

Example:

```yaml
fields:
  toolbar:
    type: toolbar
    align: spaced
    actions:
      publish:
        label: Publish
        action: publish
        icon: play
      unpublish:
        label: Unpublish
        action: unpublish
        icon: play
```

## Dashboard widgets (`type: widget`) and `widget_providers`

List and form compositions can define read-only **widgets** (metrics, cards, status, charts, and dashboard tables). Widgets that declare `provider` resolve data from PHP classes registered in `config('flatpack.widget_providers')`.

Each provider class implements `Flatpack\Widgets\Contracts\WidgetDataProvider`. Its `handle()` method must return a **typed payload** implementing `Flatpack\Widgets\Data\WidgetPayload` (Laravel `Arrayable` + `Jsonable` with an explicit `toArray()` / `toJson()` on each concrete class).

- **Metric** widgets expect `Flatpack\Widgets\Data\MetricWidgetData` (value, trend, optional description).
- **Card** widgets expect `Flatpack\Widgets\Data\CardWidgetData` (optional value, context, footer).
- **Status** widgets expect `Flatpack\Widgets\Data\StatusWidgetData` (status enum, value, context, updated label, optional description).
- **Chart** widgets expect `Flatpack\Widgets\Data\ChartWidgetData` (`points` as a list of row objects matching the chart `x_key` and series keys).
- **Table** widgets with `provider` accept an optional `columns` map in YAML using the same shape as model-backed table widgets. When `columns` is present in YAML, it takes precedence over any `columns` returned by the widget provider at runtime; the provider should focus on returning `rows`. If a provider also returns `columns` while YAML supplies them, the provider columns are silently dropped and a debug log line records the conflict. Without YAML columns, the provider may supply them at runtime as before. The widget provider returns a `Flatpack\Widgets\Data\TableWidgetData` snapshot with required `rows` and an optional `columns` map. Pagination and sorting on provider-backed tables are client-side; if you need server-driven paging or sorting, use a model-backed table widget instead. Build the snapshot directly with `new TableWidgetData(rows: [...], columns: [...])`, or use `Flatpack\Widgets\Data\Table\TableWidgetDataResolver::resolve(...)` to coerce arrays, `Collection`s, `Model` instances (optionally projected via `columnIds`), or `JsonResource`/`ResourceCollection` results into the right shape. Cells whose keys do not match a declared column id are simply ignored at the UI layer.

Model-backed table widgets (`model` without `provider`) still declare `columns` in YAML as before.

### Provider-backed table widget example

**1. YAML widget (e.g. `flatpack/dashboard/list.yaml`)**

```yaml
widgets:
    recent_orders:
        type: table
        provider: recent_orders
        label: Recent orders
        pagination: true
        showColumnsVisibility: true
```

**2. Register the provider in `config/flatpack.php`**

```php
'widget_providers' => [
    'recent_orders' => App\Widgets\RecentOrdersWidgetProvider::class,
],
```

**3a. Provider implementation using the resolver (typical case)**

```php
namespace App\Widgets;

use App\Models\Order;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\Table\TableWidgetDataResolver;
use Flatpack\Widgets\Data\TableWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class RecentOrdersWidgetProvider implements WidgetDataProvider
{
    public function __construct(private TableWidgetDataResolver $resolver) {}

    public function authorize(Authenticatable $user, WidgetContext $context): bool
    {
        return $user->can('viewAny', Order::class);
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        $orders = Order::query()->latest()->limit(20)->get();

        $snapshot = $this->resolver->resolve(
            $orders,
            columnIds: ['id', 'reference', 'total', 'href'],
        );

        return new TableWidgetData(
            rows: $snapshot->rows,
            columns: [
                'reference' => ['label' => 'Order'],
                'total' => ['label' => 'Total', 'type' => 'currency'],
                'href' => ['label' => '', 'hidden' => true],
            ],
        );
    }
}
```

**3b. Provider implementation without the resolver (manual rows)**

For ad-hoc shapes (composite metrics, third-party APIs, etc.) skip the resolver entirely:

```php
public function handle(WidgetContext $context): WidgetPayload
{
    return new TableWidgetData(
        rows: [
            ['id' => 1, 'reference' => 'ORD-001', 'total' => '$120.00', 'href' => '/flatpack/orders/1/edit'],
            ['id' => 2, 'reference' => 'ORD-002', 'total' => '$48.50', 'href' => '/flatpack/orders/2/edit'],
        ],
    );
}
```

**3c. Provider-backed table with YAML columns (recommended)**

Move the column definitions to YAML to keep them versioned and reviewable; the provider becomes a rows-only fetcher.

```yaml
widgets:
    recent_posts:
        type: table
        provider: recent_posts
        label: Recent posts
        columns:
            title:
                label: Title
                sortable: true
            status:
                label: Status
                type: badge
                options:
                    draft: { value: draft, label: Draft, status: pending }
                    published: { value: published, label: Published, status: success }
            href:
                label: ''
                hidden: true
```

```php
public function handle(WidgetContext $context): WidgetPayload
{
    $posts = Post::query()->latest()->limit(5)->get();

    return new TableWidgetData(
        rows: $this->resolver
            ->resolve($posts, columnIds: ['id', 'title', 'status', 'href'])
            ->rows,
    );
}
```

Notes:

- **Snapshot, not paginated.** Ship a fixed top-N (`limit(20)` above) and let the YAML `pagination: true` paginate that snapshot in the browser.
- **`href` row navigation.** Provider-backed table widgets navigate on row click when a row carries an `href` string.
- **YAML wins over provider columns.** If both YAML and the provider return `columns`, YAML is used and provider columns are dropped (with a debug log entry). Pick one place to define them.
- **Columns are optional.** Omitting `columns` in both YAML and the provider is fine; the runtime emits an empty columns map and the widget renders the keys it finds in `rows`. Provide `columns` whenever the labels, types, or visibility need to be controlled.

