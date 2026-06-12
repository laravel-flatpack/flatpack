# Fields and Relations

## Fields

- Keep field definitions explicit and avoid implicit defaults that vary by context.
- Reuse naming conventions for field keys to prevent serializer and UI mismatch.
- Prefer stable identifiers when renaming labels to avoid accidental data wiring changes.

### Form field preset (text / textarea only)

- Optional `preset: { field: <sourceFieldId>, type: exact|slug|url|camel|file }` auto-fills the destination from the source in the UI until the user edits the destination or it was non-empty on load.
- `field` must be another field’s **id** (the `id` key if set, otherwise the YAML key). Unknown or self-referencing presets are stripped server-side.
- Only `type: text` and `type: textarea` may define `preset`; other types drop it during normalization.

## Relations

- Relation columns should include consistent metadata:
  - `relation`
  - `relation_name` or `relationName`
  - `relation_value` or `relationValue`
- Avoid mixing naming styles within the same composition unless compatibility demands it.
- Keep relation display and relation value mappings deterministic and documented by shape.

## Compatibility

- Validate relation metadata against expected frontend consumers before merging.
- Confirm relation config works in both page mode and JSON mode outputs.

## Embedded `type: table` fields

See **`embedded-form-table.md`**.
