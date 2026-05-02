# `list.yaml` composition

Top-level structure for list index pages, optional tabbed scopes, filters, and (on dashboard entities) widgets. Normative schema: [`resources/schema/list.json`](../../resources/schema/list.json).

## Root-level keys

| Key | Type | Default / notes | Description |
| --- | --- | --- | --- |
| `name` | `string` | | Display name for the entity. |
| `model` | `string` | | Fully qualified Eloquent model class. |
| `icon` | `string` | | Lucide icon for navigation and headers. |
| `nav_order` | `integer` | | Sidebar sort weight; **lower** values appear first. |
| `menu` | `string` | `main` | `main`, `secondary`, or `bottom`. |
| `reorderable` | `boolean` \| `string` | | `true` uses default order column (`sort_order`); or pass a **column name** string for the order attribute. |
| `row_click` | `string` | `edit_page` | `none`, `edit_page`, `edit_modal`, or `edit_drawer`. |
| `columns` | array \| object | | Array of `columnDefinition`, or map column id → definition. |
| `tabs` | `object` | | Tab id → `listTabPanel`, at least one tab when present. |
| `filters` | `object` | | Filter id → `filterDefinition`, keyed by **column id**. |
| `actions` | `object` | | Header toolbar: action id → `headerActionEntry`. |
| `bulk_actions` | `object` | | Bulk action id → `bulkActionDefinition`. |
| `widgets` | `object` | | Dashboard: widget id → `widgetDefinition` (min one entry when used). |
| `default_sort` | `object` | | `defaultSort`: `key` + `direction` (`asc` \| `desc`). |
| `pagination` | `boolean` | | Force show (`true`) or hide (`false`) pagination controls. |
| `showColumnsVisibility` | `boolean` | | Show or hide the column visibility dropdown in the table toolbar. |

No other root keys are allowed (`additionalProperties: false`).

## List tab panel (`listTabPanel`)

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | Tab label. |
| `icon` | no | Lucide icon. |
| `scope` | no | Model query scope name (for example `trashed`). |
| `reorderable` | no | Overrides root `reorderable` for this tab (`boolean` or column name string). |
| `row_click` | no | Overrides root `row_click`. |
| `columns` | no | If set, replaces root `columns` for this tab (array or non-empty map). |
| `filters` | no | If set, **replaces** root `filters` entirely for this tab. |
| `bulk_actions` | no | Tab-level bulk actions (`snake_case`); replaces root when set. |
| `bulkActions` | no | Alias of `bulk_actions`. |
| `default_sort` | no | Tab-level default sort. |
| `pagination` | no | Tab-level pagination visibility override. |

## Filters (`filters`)

Each value is a `filterDefinition` (discriminated union):

### `type: select`

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `select` |
| `label` | no | Override label. |
| `placeholder` | no | |
| `multiple` | no | Boolean. |
| `options` | no | `columnOptions`: overrides column options when provided. |

### `type: date`

| Key | Required | Description |
| --- | --- | --- |
| `type` | yes | `date` |
| `label` | no | |
| `placeholder` | no | |
| `mode` | no | `exact` or `from`. |

### Label-only override (no `type`)

An object with only optional `label` and/or `placeholder` inherits the column’s filter behavior but overrides presentation. Schema: `filterOverrides` (must **not** include `type`).

## Header actions (`actions`)

Each entry is `headerActionEntry` (same overall shape as form header actions).

### Required / exclusivity

- **Required:** `label`.
- **Exactly one of:** `action` or `href`.

### Keys

| Key | Type | Description |
| --- | --- | --- |
| `icon` | `string` | |
| `variant` | `buttonVariant` | |
| `success_message` | `string` | |
| `confirm` | `boolean` | |
| `success_redirect` | `successRedirect` | |
| `enabled_if` | `actionInactiveUntil` | |
| `visible_if` | `actionInactiveUntil` | |
| `primary` | `boolean` | Primary CTA in toolbar when relevant. |
| `submit` | `boolean` | Reserved for parity with form actions; list actions do not submit forms. |
| `shortcut` | `string` | |

See [form-composition.md](./form-composition.md) for `successRedirect` and gating predicate shapes (`actionInactiveUntil`, `actionInactivePredicate`).

## Bulk actions (`bulk_actions`)

Each entry is `bulkActionDefinition`.

| Key | Required | Description |
| --- | --- | --- |
| `label` | yes | |
| `action` | yes | Must match a configured bulk handler key (`config('flatpack.bulk_actions')`). |
| `icon` | no | |
| `variant` | `buttonVariant` | |
| `success_message` | no | |
| `confirm` | no | |
| `success_redirect` | no | |
| `enabled_if` | no | |
| `visible_if` | no | |

## Default sort (`default_sort`)

| Key | Required | Description |
| --- | --- | --- |
| `key` | yes | Column id to sort by. |
| `direction` | yes | `asc` or `desc`. |

## Dashboard widgets (`widgets`)

Root-level `widgets` appears on **dashboard** list compositions (typically the entity named by `flatpack.composition.dashboard_entity`). Each entry is a `widgetDefinition`. See [widgets.md](./widgets.md).

## See also

- Columns: [list-columns.md](./list-columns.md)
- Widgets: [widgets.md](./widgets.md)
- JSON `$defs`: `listTabPanel`, `filterDefinition`, `filterSelect`, `filterDate`, `filterOverrides`, `listActionsBlock`, `headerActionEntry`, `bulkActionDefinition`, `defaultSort`, `widgetDefinition` in [`resources/schema/list.json`](../../resources/schema/list.json)
