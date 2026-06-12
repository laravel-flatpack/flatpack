![Image of package](.github/package-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flatpack/flatpack.svg?style=flat-square)](https://packagist.org/packages/flatpack/flatpack)
[![License](https://img.shields.io/github/license/laravel-flatpack/flatpack)](LICENSE.md)
[![Test Coverage](.github/badge-coverage.svg)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Tests Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/tests.yml)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Code Style Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/php-cs-fixer.yml?label=code%20style)](https://github.com/laravel-flatpack/flatpack/actions/workflows/php-cs-fixer.yml)

# Flatpack

YAML-driven admin panel for Laravel.
React and Inertia UI, declarative `form.yaml` and `list.yaml` compositions per entity.

📕 [Official Documentation](https://laravel-flatpack.com)

![Demo](.github/demo.gif)

---

## Requirements

- PHP ^8.3
- Laravel 12 or 13
- [Inertia.js for Laravel](https://inertiajs.com/) in the host application (middleware, root view, and Vite setup)
- Composer

## Installation

**1. Install the package**

```bash
composer require flatpack/flatpack
```

**2. Run the install command**

```bash
php artisan flatpack:install
```

This publishes config and compiled panel assets (`--tag=flatpack`), optionally publishes the AI YAML skill, and can add `canAccessFlatpack()` to your `User` model when you confirm.

Keep published assets in sync after updates (recommended in `composer.json`):

```json
"post-update-cmd": [
    "@php artisan vendor:publish --tag=flatpack --force"
]
```

**Manual publish** (equivalent to the first install step):

```bash
php artisan vendor:publish --tag=flatpack
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

**4. Register a Laravel policy for every model you expose in Flatpack**

Flatpack authorizes list, form, row, and bulk actions through standard model policies (`viewAny`, `view`, `create`, `update`, `delete`, and soft-delete abilities when used).

```bash
php artisan make:policy PostPolicy --model=Post
```

Register the policy in `AppServiceProvider` (or rely on Laravel’s policy discovery).

**5. Generate your first entity**

```bash
php artisan flatpack:make Post
```

This writes `flatpack/posts/form.yaml` and `flatpack/posts/list.yaml` under `config('flatpack.composition.path')` (default: `base_path('flatpack')`).

Visit `/flatpack/posts` (prefix is `config('flatpack.http.prefix')`, default `flatpack`).

## Host configuration

Flatpack ships Inertia pages and compiled frontend assets. The host app must already run [Inertia for Laravel](https://inertiajs.com/server-side-setup) (`HandleInertiaRequests`, root Blade view, Vite). Flatpack registers its own routes and does not replace your app’s frontend entrypoint.

Re-publish after package updates (`--force` in `post-update-cmd` is recommended).

| Variable | Config key | Notes |
| --- | --- | --- |
| `FLATPACK_COMPOSITION_PATH` | `composition.path` | Entity folders (`form.yaml`, `list.yaml`). Default: `base_path('flatpack')`. |
| `FLATPACK_COMPOSITION_DASHBOARD_ENTITY` | `composition.dashboard_entity` | Slug for dashboard `list.yaml`. Default: `dashboard`. |
| `FLATPACK_HTTP_PREFIX` | `http.prefix` | URL prefix for panel routes. Default: `flatpack`. |
| `FLATPACK_SECURITY_GUARD` | `security.guard` | Auth guard for panel middleware. Default: `web`. |
| `FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING` | `security.authorization.allow_when_policy_missing` | Overrides fail-closed behavior outside `local`. |
| `FLATPACK_HTTP_LOGIN_THROTTLE` | `http.login.throttle` | Throttle middleware for login POST. |
| `FLATPACK_HTTP_REGISTER_JSON_EXCEPTION_HANDLER` | `http.register_json_exception_handler` | Redirect unauthenticated JSON requests to login. |

See `config/flatpack.php` for uploads, navigation, and action handler maps.

**Custom login** — default POST is `Flatpack\Http\Controllers\SessionController@store` (`config('flatpack.http.login.store')`). To use [Laravel Fortify](https://fortify.laravel.com/) or another flow:

```php
'store' => [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'],
```

Panel routes still use `auth:{guard}` and `EnsureFlatpackAccess` (which calls `canAccessFlatpack()`).

`flatpack:make` options: `--model=`, `--entity=`, `--menu=`, `--icon=`, `--nav-order=`, and toggles such as `--without-auto-fields`. Run `php artisan flatpack:make --help`.

## Security

Panel access requires `canAccessFlatpack()` on the authenticated user.

**Model policies are required in non-local environments.** If a model has no registered policy, authorization denies the action unless `allow_when_policy_missing` is enabled.

| Setting                                       | Default                                        | Meaning                                           |
| --------------------------------------------- | ---------------------------------------------- | ------------------------------------------------- |
| `allow_when_policy_missing`                   | `true` when `APP_ENV=local`, otherwise `false` | Allow panel users to act on models with no policy |
| `FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING` | Overrides the config value in any environment  |                                                   |

In production, when `allow_when_policy_missing` is `true`, Flatpack logs a warning for each missing-policy check.

## Examples

Minimal list (`flatpack/posts/list.yaml`):

```yaml
name: Posts
model: App\Models\Post
icon: book-open
columns:
    title:
        label: Title
        type: text
        searchable: true
```

Minimal form (`flatpack/posts/form.yaml`):

```yaml
name: Posts
model: App\Models\Post
fields:
    title:
        label: Title
        type: text
    body:
        label: Body
        type: block-editor
```

Full key reference: [laravel-flatpack.com/reference](https://laravel-flatpack.com/reference)

## Schema snapshot

Quick reference for supported schema types (see the [official docs](https://laravel-flatpack.com/reference) for the full contract).

**Form field `type` values:** `text`, `textarea`, `select`, `combobox`, `date-picker`, `date-range-picker`, `time-picker`, `checkbox`, `switch`, `rich-text`, `block-editor`, `table`, `file-upload`, `toolbar`, and others — see [form field types](https://laravel-flatpack.com/reference).

**List column `type` values:** `text`, `select`, `date`, `datetime`, `actions`, `badge`, `relation` — see [list columns](https://laravel-flatpack.com/reference).

**Dashboard widget `type` values:** `metric`, `card`, `status`, `chart`, `table`, `grid` — see [widgets](https://laravel-flatpack.com/reference).

Optional **`span`** on fields and widgets: `full`, `half`, `two_thirds`, `third`, `quarter` (aliases `1/2`, `2/3`, `1/3`, `1/4`).

## AI-assisted YAML authoring

```bash
php artisan vendor:publish --tag=flatpack-ai
```

[Laravel Boost](https://github.com/laravel/boost): after `php artisan boost:install`, enable **`flatpack-host-yaml-authoring`** when editing composition YAML.

Or pass **`--with-ai`** to `flatpack:install` in non-interactive mode.

## Package development

Clone this repository and read [`.github/DEVELOPMENT.md`](.github/DEVELOPMENT.md). Before opening a PR:

```bash
composer run check
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md).

## Security vulnerabilities

See the [security policy](../../security/policy).

## Credits

- [Fausto Quaggia](https://github.com/faustoq)
- [All Contributors](../../contributors)

## License

MIT — see [LICENSE.md](LICENSE.md).
