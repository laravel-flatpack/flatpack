# Composition Structure

## Authoring Baseline

- Keep composition keys predictable and stable to reduce parser ambiguity.
- Prefer explicit, readable YAML over compact but unclear structures.
- Keep naming consistent across entity name, route-facing identifiers, and labels.

## Structural Guardrails

- Keep each entity composition focused on one concern and avoid mixed semantics.
- Avoid hidden coupling between unrelated sections by sharing names intentionally.
- If a section is repeated across entities, standardize naming rather than inventing variants.

## Safety Checks

- Ensure YAML remains valid and parser-friendly after edits.
- Confirm required keys used by loaders and schema formatters are still present.
- Preserve backward-compatible shapes when updating existing entities.
