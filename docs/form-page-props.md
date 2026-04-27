# Form Page YAML (Page-Level) Props

Page-level keys for `form.yaml`, based on `resources/schema/form.json`.

## Example

```yaml
name: Post
model: App\Models\Post
icon: book-open

fields:
  title:
    type: text
    label: Title
    required: true

tabs:
  seo:
    label: SEO
    icon: badge-info
    fields:
      slug:
        type: text
        label: Slug

actions:
  save:
    label: Save
    action: save
    variant: primary
    primary: true
    success_redirect: edit
```

## Top-Level Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `name` | `string` | No | Display name for entity/page. |
| `model` | `string` | No | Fully-qualified Eloquent model class. |
| `icon` | `string` | No | Lucide icon name. |
| `fields` | `object` | No | Root form fields map. |
| `tabs` | `object` | No | Tab map; each tab includes `label` and `fields`. |
| `actions` | `object` | No | Header/form actions map keyed by action id. |

## `tabs.<tabId>` Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `label` | `string` | Yes | Tab title. |
| `icon` | `string` | No | Optional tab icon. |
| `fields` | `object` | Yes | Tab-local fields map. |

## `actions.<actionId>` Props

| Key | Type | Required | Description |
| --- | --- | --- | --- |
| `label` | `string` | Yes | Button label. |
| `action` | `string` | Cond. | Action handler name. Mutually exclusive with `href`. |
| `href` | `string` | Cond. | Link target. Mutually exclusive with `action`. |
| `icon` | `string` | No | Icon name. |
| `variant` | `default \| outline \| secondary \| ghost \| destructive \| link \| primary` | No | Button variant (`primary` is accepted YAML alias). |
| `success_message` | `string` | No | Success toast/message. |
| `confirm` | `boolean` | No | Prompt confirmation before execution. |
| `success_redirect` | `list \| edit \| create \| show \| back \| previous \| current \| stay \| true` | No | Post-action redirect behavior (`true` aliases `list`). |
| `enabled_if` | `object` | No | Conditional enablement predicate. |
| `visible_if` | `object` | No | Conditional visibility predicate. |
| `primary` | `boolean` | No | Marks default submit/primary CTA. |
| `shortcut` | `string` | No | Keyboard shortcut metadata. |

## `enabled_if` / `visible_if` Predicate Shape

Condition object supports:

- `all`: array of predicates (all must pass)
- `any`: array of predicates (any can pass)
- `message`: optional disabled-state message

Supported predicate keys:

- `form.dirty: boolean`
- `form.mode_in: ['create' | 'edit', ...]`
- `list.selection.min: integer`
- `list.search_present: boolean`
- `list.filters_applied: boolean`

