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

## Normative reference (read these, do not guess keys)

On GitHub, browse the package repository’s **`.docs`** tree (YAML reference). If you have the package source locally, see:

- **YAML reference index:** `vendor/flatpack/flatpack/.docs/yaml-reference/README.md` may be absent from Composer installs (`.docs` is often dev-only in archives). Prefer published docs at **https://laravel-flatpack.com** or the GitHub repo’s `.docs` folder.
- **JSON contracts shipped with the package:** `vendor/flatpack/flatpack/resources/schema/form.json` and `list.json` — authoritative allowed keys for IDE/schema tooling.

## Practices

- Prefer **snake_case** keys in YAML as documented; camelCase aliases exist for compatibility where noted in schema/normalizers.
- After changing composition shape in a **fork or local package dev**, JSON schema and codegen may apply — that workflow belongs to package contributors ([`.github/DEVELOPMENT.md`](https://github.com/laravel-flatpack/flatpack/blob/main/.github/DEVELOPMENT.md)), not typical host-app installs.
- Use **`?json=true`** on Flatpack pages only for **debugging** normalized props — not as the primary runtime API.

## Laravel Boost

If you use [Laravel Boost](https://github.com/laravel/boost), run `php artisan boost:install` so skills from installed packages are available; activate this skill when editing Flatpack YAML. Alternatively copy this skill into **`.ai/skills/`** via:

```bash
php artisan vendor:publish --tag=flatpack-ai
```
