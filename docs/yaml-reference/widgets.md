# Widgets

Flatpack supports read-only **widgets** (metrics, cards, status, charts, tables) in two places:

1. **Dashboard list composition** — root `widgets` on `list.yaml` for the dashboard entity (see [`resources/schema/list.json`](../../resources/schema/list.json) root `properties.widgets`).
2. **Form composition** — a form field with `type: widget` and a nested `widget:` map containing a single widget id → definition (see [`resources/schema/form.json`](../../resources/schema/form.json) `$defs/fieldWidget`).

The discriminated union is `widgetDefinition`. **Dashboard YAML** should follow [`list.json`](../../resources/schema/list.json). **`form.json` duplicates** the same union with small differences for embedded table widgets (for example `actions` and `filters` shapes on `type: table`); when authoring widgets inside forms, match [`resources/schema/form.json`](../../resources/schema/form.json) `$defs/widgetDefinition`.

### Layout (`span`)

Optional **`span`** on any widget uses the same tokens as form fields: `full`, `half`, `two_thirds`, `third`, `quarter`, plus aliases `1/2`, `2/3`, `1/3`, `1/4` (normalized server-side). The dashboard renders widgets in a **1 / 2 / 4** column grid (default / `md` / `xl`). When `span` is omitted, **chart** and **table** widgets still default to a full-width row at `xl`; **metric**, **card**, and **status** default to a single column.

```yaml
widgets:
  revenue_chart:
    type: chart
    provider: sales_chart
    label: Revenue
    span: full
  active_users:
    type: metric
    provider: active_users
    label: Active users
    value_format: { kind: number }
    period: { kind: day }
    span: 1/4
```

---

## Provider resolution

Widgets that declare a **`provider`** string resolve PHP classes from the host config:

```php
// config/flatpack.php
'widget_providers' => [
    'my_metric' => App\Widgets\MyMetricProvider::class,
],
```

The key (`my_metric`) must match YAML `provider: my_metric`. Resolution is implemented in `Flatpack\Services\Runtime\WidgetRuntime::resolveProvider()`; if the key is missing or the class does not implement `Flatpack\Widgets\Contracts\WidgetDataProvider`, the dashboard logs an error and skips data for that widget.

**Auth:** `WidgetDataProvider::authorize()` runs before `handle()`. Unauthenticated or unauthorized users do not receive widget data.

Model-backed **table** widgets (`type: table` with `model` and without `provider`) load rows through Flatpack’s list stack instead of a custom provider (see below).

---

## `type: metric`

**Required in YAML:** `type`, `provider`, `label`, `value_format`, `period`.

| Key | Description |
| --- | --- |
| `provider` | Config key under `widget_providers`. |
| `label` | Widget title. |
| `description` | Optional subtitle or help text. |
| `value_format` | `metricValueFormat` (see below). |
| `period` | `metricPeriod` (see below). |
| `trend` | Optional `metricTrendOptions` (`precision` integer 0–4). |

### `value_format` (`metricValueFormat`)

| Key | Required | Description |
| --- | --- | --- |
| `kind` | yes | `number`, `currency`, or `percentage`. |
| `currency` | if currency | ISO 4217 string, length 3. |
| `minimumFractionDigits` | no | 0–6. |
| `maximumFractionDigits` | no | 0–6. |

### `period` (`metricPeriod`)

| Key | Required | Description |
| --- | --- | --- |
| `kind` | yes | `day`, `week`, `month`, `quarter`, `year`, or `custom`. |
| `lookback` | no | Integer ≥ 1 (for custom-style ranges). |
| `label` | no | Human-readable period label. |

### PHP payload

Return `Flatpack\Widgets\Data\MetricWidgetData` (`value`, `MetricTrend`, optional `description`).

---

## `type: card`

**Required:** `type`, `provider`, `label`.

| Key | Description |
| --- | --- |
| `description` | Optional. |
| `data` | Optional static `cardWidgetData` in YAML; usually filled by the provider at runtime. |

**`cardWidgetData`:** optional `status` (`default`, `error`, `info`, `success`, `warning`), `value` (number or string), `context`, `updated_at`, `description`.

### PHP payload

Return `Flatpack\Widgets\Data\CardWidgetData` (`value`, `context`, `footer`).

---

## `type: status`

**Required:** `type`, `provider`, `label`.

Same optional `description` and `data` (`cardWidgetData`) as `card`.

### PHP payload

Return `Flatpack\Widgets\Data\StatusWidgetData` (`Status` enum, `value`, `context`, `updated_at`, optional `description`).

---

## `type: chart`

**Required:** `type`, `provider`, `label`, `chart`.

| Key | Description |
| --- | --- |
| `provider` | Config key. |
| `description` | Optional. |
| `chart` | `chartWidgetConfig` (see below). |
| `data` | Omitted in YAML; runtime `chartWidgetResolvedData` from provider (`points`). |

### `chart` (`chartWidgetConfig`)

| Key | Required | Description |
| --- | --- | --- |
| `series` | yes | Non-empty array of `{ key, label, color? }`. `color` may be an `optionStatus` token or any CSS color string. |
| `x_key` | no | Data key for X axis (defaults described in schema; commonly `date`). |
| `mode` | no | `area`, `bar`, or `line`. |
| `variant` | no | For area mode: `area_stacked` or `area`. |
| `time_ranges` | no | Optional `{ id, label }[]` for range toggles in the UI. |

### PHP payload

Return `Flatpack\Widgets\Data\ChartWidgetData` with a `points` array: each point must include keys matching `x_key` (default `date`) and each `series.key`.

---

## `type: table`

Two variants:

### A) Provider-backed (`provider` set, no `model`)

**Required:** `type`, `provider`.

Optional: `label`, `description`, `icon`, `columns`, `actions`, `filters`, `pagination`, `showColumnsVisibility`, `empty_state`, `default_sort`, `data`.

| Key | Description |
| --- | --- |
| `columns` | Optional YAML column map. When present, **takes precedence** over `columns` returned by the provider (provider columns may be dropped; debug logs may record conflicts). |
| `actions` | Toolbar: in **`list.json`**, `tableWidgetToolbarActionsYaml` (array or map of `columnActionButton`). |
| `filters` | In **`list.json`**, object of `filterDefinition` entries. |
| `pagination` | Boolean or `{ per_page?, page_sizes? }`. |
| `empty_state` | `{ title?, description? }`. |
| `default_sort` | `{ key, direction }`. |
| `data` | Runtime resolved `{ rows?, sorting? }`; not authored in YAML for provider widgets. |

Provider-backed tables use **client-side** pagination/sorting over the returned snapshot. For server-driven paging/sorting, prefer **model-backed** tables.

### B) Model-backed (`model` set, typically no `provider`)

**Required:** `type`, `model`, `columns`.

Optional: `entity`, `list_entity` (Flatpack entity slug for toolbar list actions; alias pair), `label`, `description`, `icon`, `actions`, `bulk_actions`, `filters`, `pagination`, `showColumnsVisibility`, `empty_state`, `default_sort`, `data`.

`bulk_actions` uses `tableWidgetBulkActionsYaml` (array or map of `bulkActionDefinition`).

---

## Form field `type: widget`

Example:

```yaml
fields:
    health_summary:
        type: widget
        label: System status
        widget:
            overall_status:
                type: status
                provider: system_status
                label: Overall
```

The inner map must contain **exactly one** widget id → definition. Shapes follow **`form.json`** `widgetDefinition` (duplicate defs). For **table** widgets inside forms, check `form.json` for exact `actions` / `filters` types (they differ slightly from `list.json`).

---

## PHP: `WidgetDataProvider`

Implement `Flatpack\Widgets\Contracts\WidgetDataProvider`:

- `authorize(Authenticatable $user, WidgetContext $context): bool`
- `handle(WidgetContext $context): WidgetPayload`

`WidgetContext` carries the current request, entity, widget id, and the **merged** definition array (YAML + resolved data where applicable).

### Example: provider-backed table

**1. YAML** (`flatpack/dashboard/list.yaml` or any list composition that defines `widgets`):

```yaml
widgets:
    recent_orders:
        type: table
        provider: recent_orders
        label: Recent orders
        pagination: true
        showColumnsVisibility: true
```

**2. Config** (`config/flatpack.php`):

```php
'widget_providers' => [
    'recent_orders' => App\Widgets\RecentOrdersWidgetProvider::class,
],
```

**3. Provider** (uses `TableWidgetDataResolver` to map models to rows):

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

You may return **rows only** and define **`columns` entirely in YAML** (recommended for reviewable layouts); the resolver still projects row keys.

**Navigation:** rows that include an `href` string are typically used for row-click navigation in provider-backed table widgets.

---

## See also

- [list-composition.md](./list-composition.md) — root `widgets` on dashboard lists
- [form-field-types.md](./form-field-types.md) — `type: widget` field
- Source: `Flatpack\Services\Runtime\WidgetRuntime`, `Flatpack\Http\Controllers\Concerns\ResolvesWidgets`
- JSON `$defs`: `widgetDefinition`, `metricWidget`, `cardWidget`, `statusWidget`, `chartWidget`, `tableWidgetProviderBacked`, `tableWidgetModelBacked` in [`resources/schema/list.json`](../../resources/schema/list.json)
