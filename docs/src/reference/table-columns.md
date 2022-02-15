# Lists

Display a list of records in auto-paginated, sortable and filterable table.

**Example**

```yaml
columns:
    id:
        label: ID
        sortable: true
    name:
        label: Name
        sortable: true
    email:
        label: Email
        sortable: true
        searchable: true
    created_at:
        label: Created
        type: datetime
        format: "Y-m-d H:i:s"
        sortable: true
    updated_at:
        label: Updated
        type: datetime
        format: "Y-m-d H:i:s"
        sortable: true
```

## Column Options

| Option     | Description                                                        | Required | Default    |
| :--------- | :----------------------------------------------------------------- | :------- | :--------- |
| type       | Column type (see [Column Types](#column-types))                    | No       | `text`     |
| label      | Column heading text (default: column name)                         | No       | column key |
| invisible  | Determine if the column is invisible (it can be changed by user)   | No       | `false`    |
| searchable | Determine if the column value should be included in search results | No       | `false`    |
| sortable   | Determine if the column can be used for sorting records            | No       | `false`    |

## Column Types

### Text

-   Type: `text`

### Datetime

-   Type: `datetime`

#### Datetime Column Options

| Option | Description      | Required | Default     |
| :----- | :--------------- | :------- | :---------- |
| format | Date time format | No       | `Y-m-d H:i` |

### Boolean

-   Type: `boolean`

## List Toolbar
