---
name: flatpack-host-yaml-authoring
description: "Author and validate Flatpack entity compositions (form.yaml, list.yaml) in a host Laravel app. Use when adding entities, fields, columns, actions, relations, or adjusting config/flatpack.php — not for hacking package internals."
license: MIT
---

# Flatpack YAML authoring (host application)

You are working in an application that uses **`flatpack/flatpack`**. Entity behavior is declared under the configured composition path (often `flatpack/{entity}/form.yaml` and `list.yaml`).

## When to use this skill

- Creating or editing **form** or **list** compositions, tabs, filters, header actions, bulk actions, or embedded `type: table` fields.
- Wiring **relations**, **actions**, or **widgets** in YAML.
- Adjusting **Flatpack config** (`config/flatpack.php`) for the panel.

## Rules index

| Topic | File |
|-------|------|
| Composition shape, naming, entity sections | `rules/structure.md` |
| Field hygiene, relations, `type: table` pointer | `rules/fields-relations.md` |
| List/header/bulk actions, runtime compatibility | `rules/actions-runtime.md` |
| Pre-merge checks, schema contracts | `rules/validation-workflow.md` |
| Embedded `type: table` (toolbar, relation gate, row drawer) | `rules/embedded-form-table.md` |

## Normative reference (read these, do not guess keys)

- **Official docs:** https://laravel-flatpack.com/reference
- **JSON contracts shipped with the package:** `vendor/flatpack/flatpack/resources/schema/form.json` and `list.json` — authoritative allowed keys for IDE/schema tooling.

## Practices

- Prefer **snake_case** keys in YAML as documented; camelCase aliases exist for compatibility where noted in schema/normalizers.
- Use **`?json=true`** on Flatpack pages only for **debugging** normalized props — not as the primary runtime API.

## Install this skill

**Laravel Boost:** run `php artisan boost:install` so skills from installed packages are available; activate **`flatpack-host-yaml-authoring`** when editing Flatpack YAML.

**Without Boost**, copy into `.ai/skills/`:

```bash
php artisan vendor:publish --tag=flatpack-ai
```

Or confirm during `php artisan flatpack:install` (interactive), or pass `--with-ai` with `--no-interaction`.
