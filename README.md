![Image of package](.github/package-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flatpack/flatpack.svg?style=flat-square)](https://packagist.org/packages/flatpack/flatpack)
[![License](https://img.shields.io/github/license/laravel-flatpack/flatpack)](LICENSE.md)
[![Test Coverage](.github/badge-coverage.svg)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Tests Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/tests.yml)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Code Style Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/lint.yml?label=code%20style)](https://github.com/laravel-flatpack/flatpack/actions/workflows/lint.yml)

# Flatpack

Declare your admin panel in YAML. Flatpack lets you build Laravel admin panels by describing resources, lists, forms, and actions in YAML instead of hand-coding CRUD screens.

Flatpack is a Laravel package for building internal admin panels from YAML compositions. It ships with a ready-to-use Inertia/React interface, so your app only needs to define what the panel should expose.

Official documentation: **[laravel-flatpack.com](https://laravel-flatpack.com)**

![Demo](.github/demo.gif)

## Why Flatpack?

- Build internal CRUD panels quickly.
- Keep admin configuration close to your Laravel app.
- Describe lists and forms declaratively in YAML files.
- Use the included panel UI without building a separate frontend.

## Quick start

Install and setup the package

```bash
composer require flatpack/flatpack
php artisan flatpack:install
```

Create your first Flatpack panel

```bash
php artisan flatpack:make
```

## Example

A list composition defines the index table for an entity:

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

A form composition defines create/edit fields:

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

## Installation

**Requirements:** PHP ^8.3 and Laravel 12 or 13.

```bash
composer require flatpack/flatpack
php artisan flatpack:install
```

`flatpack:install` publishes `config/flatpack.php` and compiled panel assets, optionally publishes the AI YAML skill (`--with-ai` or interactive prompt), and can add `canAccessFlatpack()` to your User model.

For more scaffolding options, run:

```bash
php artisan flatpack:make --help
```

## Access control

Flatpack only allows authenticated users whose model exposes `canAccessFlatpack()`.

```php
class User extends Authenticatable
{
    public function canAccessFlatpack(): bool
    {
        return $this->is_admin;
    }
}
```

## Configuration

The knobs most apps touch:

- `FLATPACK_COMPOSITION_PATH` — where your YAML compositions live (default: `flatpack/`)
- `FLATPACK_HTTP_PREFIX` — URL prefix for panel routes (default: `flatpack`)

See [`config/flatpack.php`](config/flatpack.php) and the [documentation site](https://laravel-flatpack.com) for uploads, navigation, login customization, and more.

## Updating

Flatpack ships compiled panel assets. After updating the package, republish them:

```bash
php artisan vendor:publish --tag=flatpack --force
```

To keep assets in sync automatically, add this to your `composer.json`:

```json
"post-update-cmd": [
    "@php artisan vendor:publish --tag=flatpack --force"
]
```

## Testing

You can run the tests with:

```bash
composer run test
```

Run tests with coverage:

```bash
composer run test-coverage
```

Clone this repository and see [`.github/DEVELOPMENT.md`](.github/DEVELOPMENT.md) for local development setup.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, email [fausto.quaggia@gmail.com](mailto:fausto.quaggia@gmail.com?subject=Flatpack%20Security%20Issue) instead of using the issue tracker. See [SECURITY](.github/SECURITY.md) for details.

## Credits

- [Fausto Quaggia](https://github.com/faustoq)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
