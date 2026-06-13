# Actions and Runtime Compatibility

## Action Authoring

- Keep action definitions explicit and minimal; avoid overloaded action payloads.
- Use consistent action naming across YAML, backend handlers, and frontend consumption.
- Prefer predictable parameter shapes to reduce action parsing edge cases.

## Runtime Normalization

- Assume YAML actions are normalized and sanitized server-side before rendering.
- Avoid frontend logic that depends on raw, unnormalized YAML action shapes.
- Preserve compatibility with existing sanitized action contracts during refactors.

## Output Parity

- Ensure action behavior is consistent between page output and `?json=true`.
- Validate that action visibility and metadata are stable across entity contexts.
