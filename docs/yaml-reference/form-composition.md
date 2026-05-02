# `form.yaml` composition

Top-level structure for entity form pages. Normative schema: [`resources/schema/form.json`](../../resources/schema/form.json) (root `properties`).

## Root-level keys

All keys are optional at the schema level, but in practice you typically define at least `fields` and/or `tabs`, plus `model` for record-backed forms.

| Key | Type | Description |
| --- | --- | --- |
| `name` | `string` | Display name for the entity (navigation, headers). |
| `model` | `string` | Fully qualified Eloquent model class name. |
| `icon` | `string` | Lucide icon name for navigation and headers. |
| `fields` | `object` | Map of field id → field definition (`$ref: #/$defs/fieldDefinition`). Fields declared only here render **above** tab panels when `tabs` is present. |
| `tabs` | `object` | Map of tab id → tab panel (`formTabPanel`). Must contain at least one entry when used (`minProperties: 1`). Tab `fields` merge with root `fields` into a single form values payload. |
| `actions` | `object` | Map of action id → header action (`headerActionDefinition`). |

No other root keys are allowed (`additionalProperties: false`).

## Tab panel (`formTabPanel`)

| Key | Required | Type | Description |
| --- | --- | --- | --- |
| `label` | yes | `string`, min length 1 | Tab label. |
| `icon` | no | `string` | Optional Lucide icon. |
| `fields` | yes | `object` | Map of field id → `fieldDefinition`, at least one field (`minProperties: 1`). |

## Header actions (`actions`)

Each entry uses `headerActionDefinition`.

### Required and exclusivity

- **Required:** `label` (non-empty string).
- **Exactly one of:** `action` (registered Flatpack action key) **or** `href` (navigation URL). You cannot set both.

### Optional keys

| Key | Type | Description |
| --- | --- | --- |
| `icon` | `string` | Lucide icon name. |
| `variant` | `buttonVariant` | `default`, `outline`, `secondary`, `ghost`, `destructive`, `link`, `primary` (`primary` is normalized toward default styling in UI code). |
| `success_message` | `string` | Toast or feedback message after success. |
| `confirm` | `boolean` | When true, confirm before running. |
| `success_redirect` | `successRedirect` | Where to redirect after success (see below). |
| `enabled_if` | `actionInactiveUntil` | Disables the control until predicates pass. |
| `visible_if` | `actionInactiveUntil` | Hides the control until predicates pass. |
| `primary` | `boolean` | When true, treated as the primary CTA; if several are primary, first in YAML order wins for implicit submit behavior. |
| `submit` | `boolean` | When **true**, invokes the form submit endpoint with current `values`. When **false** or omitted, runs as a **record action** without posting the full form payload. Default: false. |
| `shortcut` | `string` | Keyboard shortcut token when wired in the app. |

### `submit` semantics

- `submit: true` — posts to the form submit route with current form values (validation applies as configured).
- `submit: false` or omitted — runs the named `action` like a command on the record (typical for delete, sync, etc.); does not send full form values. Direct actions generally require **edit** mode (existing record).

### `success_redirect` (`successRedirect`)

Either:

- A keyword string: `list`, `edit`, `create`, `show`, `back`, `previous`, `current`, `stay`.
- The boolean `true` — alias for `list` (entity index).

### Action gating (`enabled_if` / `visible_if`)

Value type: `actionInactiveUntil`.

| Key | Type | Description |
| --- | --- | --- |
| `all` | array | Non-empty list of predicates (`actionInactivePredicate`); **all** must pass. |
| `any` | array | Non-empty list of predicates; **any** must pass. |

Exactly one of `all` or `any` must be present.

Optional **`message`**: non-empty string shown when the action is disabled or hidden (UX hint).

### Predicates (`actionInactivePredicate`)

Each predicate is a **single-key** object (exactly one of):

| Key | Value type | Meaning |
| --- | --- | --- |
| `form.dirty` | `boolean` | Matches whether the form has unsaved changes. |
| `form.mode_in` | array of `create` \| `edit` | Matches current form mode. |
| `list.selection.min` | integer ≥ 0 | Minimum selected rows on a list (when this action is reused in list contexts). |
| `list.search_present` | `boolean` | Search box has text. |
| `list.filters_applied` | `boolean` | Filters are applied. |

Form pages primarily use `form.dirty` and `form.mode_in`.

### Example

```yaml
actions:
    save:
        label: Save
        action: save
        submit: true
        primary: true
        success_redirect: edit
    delete:
        label: Delete
        action: delete
        variant: destructive
        confirm: true
        enabled_if:
            all:
                - { form.mode_in: [edit] }
```

## See also

- Field definitions: [form-field-types.md](./form-field-types.md)
- JSON `$defs`: `formTabPanel`, `headerActionDefinition`, `buttonVariant`, `successRedirect`, `actionInactiveUntil`, `actionInactivePredicate` in [`resources/schema/form.json`](../../resources/schema/form.json).
