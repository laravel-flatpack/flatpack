# Validation Workflow

## Documentation-first check

1. **Normative reference:** https://laravel-flatpack.com/reference
2. **Shipped contracts:** `vendor/flatpack/flatpack/resources/schema/form.json` and `list.json` — match edits to allowed keys.
3. **README snapshot:** the package README schema snapshot is a convenience summary; if it disagrees with schema or the official docs, trust schema and laravel-flatpack.com.

## Fast pre-run checks

1. Confirm YAML parses cleanly.
2. Confirm expected schema keys still exist after the change.
3. Confirm relation/action key naming is internally consistent.

## Runtime Verification

1. Load the affected entity list/form flow in the panel.
2. Verify schema-dependent UI renders without fallback or shape errors.
3. Verify `?json=true` output remains compatible with page behavior.

## Debugging tips

- Use `?json=true` on a Flatpack page to inspect normalized props when UI behavior does not match YAML intent.
- Compare relation column metadata (`relation`, `relation_name`, `relation_value`) against working entities.
- Check Laravel policies for the model referenced in composition YAML when actions are denied.
