# Forms

Display a form with fields mapped to model attributes, define custom actions, validations, etc.

**Example**

```yaml
# /flatpack/posts/form.yaml

header:
    title:
        type: heading
        size: large

toolbar:
    save:
        type: button
        label: Save
        action: save
        shortcut: s

main:
    title:
        label: Title
        placeholder: Post title
        rules: required|string

    slug:
        label: Slug
        placeholder: Post title slug
        span: 1

    category:
        label: Category
        type: relation
        relation:
            name: category
            display: name
        span: 1

    body:
        label: Post body
        type: textarea
        group: more

sidebar:
    created_at:
        label: Created
        type: datetimepicker
        span: 1
        group: Info

    updated_at:
        label: Updated
        type: datetimepicker
        span: 1
        group: Info
```

## Form Slots

| Slot      | Description         |
| :-------- | :------------------ |
| `header`  | Form headings       |
| `toolbar` | Form buttons        |
| `main`    | Main form fieldset  |
| `sidebar` | Sidebar form fields |

## Form Field Options

| Option      | Description                                                              | Default |
| :---------- | :----------------------------------------------------------------------- | :------ |
| type        | Form field type (see [Field Types](#field-types))                        | `text`  |
| label       | Field label text                                                         | ``      |
| placeholder | Field placeholder text                                                   | ``      |
| span        | Field span (Allowed values: `half` for 50% width, `full` for 100% width) | `full`  |
| group       | Name of the box to include the field within                              | ``      |

## Form Field Types

### Datetime picker

-   Type: `datetimepicker`

### E-mail input

-   Type: `email`

### Text input

-   Type: `text`

### Textarea input

-   Type: `textarea`
