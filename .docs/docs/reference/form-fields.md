# Forms

Display a form with fields mapped to model attributes, define custom actions, validations, etc.

::: details View form.yaml example
@[code](../examples/posts/advanced-form.yaml)
:::

## Content slots

An advanced version of `form.yaml` composition can be defined using form content slots:

| Slot        | Description    | Allowed field types            |
| :---------- | :------------- | :----------------------------- |
| **header**  | Heading fields | `heading` or `editable`        |
| **toolbar** | Action buttons | `button` or `link`             |
| **main**    | Main field set | Any [form field](#form-fields) |
| **sidebar** | Sidebar fields | Any [form field](#form-fields) |

Example:
@[code](../examples/form-slots.yaml)

If you don't want to use content slots, you can simply put all your fields under the **fields** definition.

```yaml
fields:
    # All form fields in one boxed fieldset
```

## Form Fields

### Field options

| Option      | Description                                                                                                                      |
| :---------- | :------------------------------------------------------------------------------------------------------------------------------- |
| type        | Form field type (see [Field Types](#field-types))                                                                                |
| label       | Field label text.                                                                                                                |
| placeholder | Field placeholder text.                                                                                                          |
| group       | Name of the grouping box to include the field within.                                                                            |
| span        | `half` for 50% width, `full` for 100% width.                                                                                     |
| rules       | String of validation rules (uses [Laravel Validation rules](https://laravel.com/docs/8.x/validation#available-validation-rules)) |

### Field types

<div class="columns"><div class="column">

-   [Text](#text-input)
-   [Email](#email-input)
-   [Textarea](#textarea-input)

</div><div class="column">

-   [Datetime Picker](#datetime-picker)
-   [Editable](#editable)
-   [Heading](#heading)

</div><div class="column">

-   [Button](#button)
-   [Relation](#relation)

</div></div>

### Text input

`text` - renders a single line text input box. This is the **default type** used if none is specified.

### Email input

`email` - renders a single line email input box.

### Textarea input

`textarea` - renders a textarea input box.

### Datetime picker

`datetimepicker` - renders a datetime picker input box.

### Heading

`heading` - renders a read-only heading field.

### Editable

`editable` - renders a heading that becomes a text input on click.

### Relation

`relation` - renders a list of checkboxes.

### Button

`button` - renders a button.

## Form Validation

form validation
