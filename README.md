![Image of package](.github/package-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flatpack/flatpack.svg?style=flat-square)](https://packagist.org/packages/flatpack/flatpack)
[![License](https://img.shields.io/github/license/laravel-flatpack/flatpack)](LICENSE.md)
[![Test Coverage](.github/badge-coverage.svg)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Tests Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/tests.yml)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Code Style Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/lint.yml?label=code%20style)](https://github.com/laravel-flatpack/flatpack/actions/workflows/lint.yml)

# Flatpack

Declare your admin panel in YAML. Laravel + Inertia/React UI included.

📕 [Official Documentation](https://laravel-flatpack.com)

![Demo](.github/demo.gif)

Here are a few short examples of what you can do.

A list page — searchable columns, icons, the works:

```yaml
# flatpack/posts/list.yaml
name: Posts
model: App\Models\Post
icon: book-open
columns:
    title:
        label: Title
        type: text
        searchable: true
    actions:
        type: actions
        actions:
            - label: Edit
              action: edit
```

A form — fields, rich text, done:

```yaml
# flatpack/posts/form.yaml
name: Posts
model: App\Models\Post
fields:
    title:
        label: Title
        placeholder: Enter a post title
        type: text
    body:
        label: Body
        type: block-editor
```

Scaffold a `Post` entity and visit `/flatpack/posts` to see it live.

## Documentation

You'll find the full guide at **[laravel-flatpack.com](https://laravel-flatpack.com)** — installation, field types, list columns, widgets, actions, and the complete YAML reference at [laravel-flatpack.com/reference](https://laravel-flatpack.com/reference).

## Installation

Requires PHP ^8.3 and Laravel 12 or 13.

**1. Install the package**

```bash
composer require flatpack/flatpack
```

**2. Run the install command**

```bash
php artisan flatpack:install
```

This publishes config and compiled panel assets, optionally publishes the AI YAML skill, and can add `canAccessFlatpack()` to your `User` model.

Keep published assets in sync after updates — add this to your `composer.json`:

```json
"post-update-cmd": [
    "@php artisan vendor:publish --tag=flatpack --force"
]
```

**3. Gate panel access on your `User` model**

```php
class User extends Authenticatable
{
    public function canAccessFlatpack(): bool
    {
        return true; // tighten for your app (role, admin flag, etc.)
    }
}
```

## Scaffolding

Generate your first entity:

```bash
php artisan flatpack:make Post
```

You get `flatpack/posts/form.yaml` and `flatpack/posts/list.yaml` under `config('flatpack.composition.path')` (default: `flatpack/`). Open `/flatpack/posts` to use the panel.

Useful flags: `--model=`, `--entity=`, `--without-auto-fields`, and more — run `php artisan flatpack:make --help`.

Flatpack ships pre-built frontend assets. Your Laravel app does not need a separate Inertia or Vite setup for the panel.

## Configuration

The knobs most apps touch:

- `FLATPACK_COMPOSITION_PATH` — where your YAML compositions live (default: `flatpack/`)
- `FLATPACK_HTTP_PREFIX` — URL prefix for panel routes (default: `flatpack`)
- `FLATPACK_SECURITY_GUARD` — auth guard for panel middleware (default: `web`)

See [`config/flatpack.php`](config/flatpack.php) and the [host configuration docs](https://laravel-flatpack.com) for uploads, navigation, and login customization.

For AI-assisted YAML authoring, run `php artisan vendor:publish --tag=flatpack-ai` or pass `--with-ai` to `flatpack:install`.

## Security

Panel access requires `canAccessFlatpack()` on the authenticated user.

If you discover a security vulnerability, email hello@faustoquaggia.com instead of using the issue tracker. See the [security policy](https://github.com/laravel-flatpack/flatpack/security/policy).

## Testing

You can run the tests with:

```bash
composer run test
```

Coverage (requires Xdebug):

```bash
composer run test-coverage
```

Before opening a PR, run the full gate:

```bash
npm run check:All
```

CI runs on PHP 8.3, 8.4, and 8.5 via [`.github/workflows/tests.yml`](.github/workflows/tests.yml). The [coverage badge](.github/badge-coverage.svg) is updated on the PHP 8.3 job. Frontend components in `resources/js/` are covered by Vitest.

Clone this repository and read [`.github/DEVELOPMENT.md`](.github/DEVELOPMENT.md) for local Vite, schema keys, and test ownership.

## Upgrading

After `composer update`, republish panel assets so hashed Vite files in `public/vendor/flatpack/build/` stay in sync:

```bash
php artisan vendor:publish --tag=flatpack --force
```

The `post-update-cmd` hook above is the easiest way to keep this automatic. Stale asset chunks are not removed on republish — use `--force` after every upgrade.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Credits

- [Fausto Quaggia](https://github.com/faustoq)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
