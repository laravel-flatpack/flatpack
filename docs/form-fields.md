# Available Form Fields

Supported field `type` values are defined in `resources/schema/form.json`.

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

