# Lists

Display a list of records in auto-paginated, sortable and filterable table.

**Example**

```yaml
# /flatpack/posts/list.yaml

columns:
    id:
        label: ID
        sortable: true

    title:
        label: Title
        sortable: true
        searchable: true

    is_active:
        label: Active
        type: boolean
        sortable: false

    created_at:
        label: Created
        type: datetime
        format: "Y-m-d H:i"
        sortable: true
        invisible: true

    updated_at:
        label: Last edit
        type: datetime
        format: "Y-m-d H:i"
        sortable: true
```

## Column Options

| Option     | Description                                                        | Default    |
| :--------- | :----------------------------------------------------------------- | :--------- |
| type       | Column type (see [Column Types](#column-types))                    | `text`     |
| label      | Column heading text (default: column name)                         | column key |
| invisible  | Determine if the column is invisible (it can be changed by user)   | `false`    |
| searchable | Determine if the column value should be included in search results | `false`    |
| sortable   | Determine if the column can be used for sorting records            | `false`    |

## Column Types

### Text

-   Type: `text`

### Datetime

-   Type: `datetime`

#### Datetime column options

| Option | Description      | Default                               |
| :----- | :--------------- | :------------------------------------ |
| format | Date time format | `Y-m-d H:i` _(e.g. 2022-01-30 18:45)_ |

Note: Check [PHP DateTime](https://www.php.net/manual/en/datetime.format.php) for the full list of recognized characters as **format** option.

### Boolean

-   Type: `boolean`
