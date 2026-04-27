# List Page YAML (Page-Level) Props

Page-level keys for `list.yaml`, based on `resources/schema/list.json`.

## Example

```yaml
name: Post
model: App\Models\Post
icon: book-open
nav_order: 10
reorderable: sort_order
row_click: edit_page

columns:
  title:
    label: Title
    sortable: true
    searchable: true

tabs:
  published:
    label: Published
    scope: published
    row_click: edit_page

actions:
  create:
    label: New Post
    href: /backend/posts/create
    variant: primary

bulk_actions:
  archive:
    label: Archive selected
    action: archive
    confirm: true
```

## Top-Level Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `name` | `string` | No | Display name for entity/page. |
| `model` | `string` | No | Fully-qualified Eloquent model class. |
| `icon` | `string` | No | Lucide icon name. |
| `nav_order` | `integer` | No | Sidebar ordering weight. |
| `reorderable` | `boolean \| string` | No | Enables drag reorder (`true` default column, or custom column name). |
| `row_click` | `none \| edit_page \| edit_modal \| edit_drawer` | No | Row click behavior. |
| `columns` | `array \| object` | No | Column definitions (root set). |
| `tabs` | `object` | No | Tab panels keyed by tab id. |
| `filters` | `object` | No | Filter overrides/definitions keyed by column id. |
| `actions` | `object` | No | Header toolbar actions map. |
| `bulk_actions` | `object` | No | Bulk actions map. |

## `tabs.<tabId>` Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `label` | `string` | Yes | Tab title. |
| `icon` | `string` | No | Optional tab icon. |
| `scope` | `string` | No | Query scope on model for that tab. |
| `reorderable` | `boolean \| string` | No | Tab override for row reordering. |
| `row_click` | `none \| edit_page \| edit_modal \| edit_drawer` | No | Tab override for row click behavior. |
| `columns` | `array \| object` | No | Tab-specific column set. |
| `filters` | `object` | No | Tab-specific filters (replaces root filters for tab). |
| `bulk_actions` / `bulkActions` | `object` | No | Tab-specific bulk actions. |

## `actions.<actionId>` Props

Same shape as form actions, with one-of requirement: exactly one of `action` or `href`.

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `label` | `string` | Yes | Button label. |
| `action` | `string` | Cond. | Action handler name. |
| `href` | `string` | Cond. | Link target. |
| `icon` | `string` | No | Icon name. |
| `variant` | `default \| outline \| secondary \| ghost \| destructive \| link \| primary` | No | Visual variant. |
| `success_message` | `string` | No | Success feedback message. |
| `confirm` | `boolean` | No | Confirmation requirement. |
| `success_redirect` | `list \| edit \| create \| show \| back \| previous \| current \| stay \| true` | No | Post-action redirect. |
| `enabled_if` | `object` | No | Conditional enablement predicate. |
| `visible_if` | `object` | No | Conditional visibility predicate. |
| `primary` | `boolean` | No | Marks primary CTA. |
| `shortcut` | `string` | No | Keyboard shortcut metadata. |

## `bulk_actions.<id>` Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `label` | `string` | Yes | Action label. |
| `action` | `string` | Yes | Bulk action handler name. |
| `icon` | `string` | No | Icon name. |
| `variant` | `default \| outline \| secondary \| ghost \| destructive \| link \| primary` | No | Visual variant. |
| `success_message` | `string` | No | Success feedback message. |
| `confirm` | `boolean` | No | Confirmation requirement. |
| `success_redirect` | `list \| edit \| create \| show \| back \| previous \| current \| stay \| true` | No | Post-action redirect. |
| `enabled_if` | `object` | No | Conditional enablement predicate. |
| `visible_if` | `object` | No | Conditional visibility predicate. |

## Reordering column behavior

`reorderable` accepts both boolean and string values:

- `reorderable: true` uses the default reorder column (`sort_order`).
- `reorderable: custom_column_name` uses that exact column for drag-and-drop persistence.

Example:

```yaml
reorderable: custom_column_name
```

Persistence requirements:

- The configured reorder column must exist in the entity database table.
- The value must be writable by the model/update flow.
- On drag-and-drop, Flatpack persists the new row order by updating that column per row.
- If the column is missing, reorder fails and the UI shows an error toast.
- No trait is required on your model; reordering is handled internally by Flatpack.
- The reorder endpoint is rate limited with `throttle:60,1`.

Migration snippet:

```php
$table->unsignedBigInteger('sort_order')->default(0)->index();
// or for a custom column:
$table->unsignedBigInteger('sorting_order')->default(0)->index();
```

If the table already has rows, initialize contiguous values before enabling `reorderable`:

```php
Post::orderBy('id')->each(fn($p, $i) => $p->update(['sort_order' => $i + 1]));
```

