![Image of package](.github/package-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/flatpack/flatpack.svg?style=flat-square)](https://packagist.org/packages/flatpack/flatpack)
[![License](https://img.shields.io/github/license/laravel-flatpack/flatpack)](LICENSE.md)
[![Test Coverage](.github/badge-coverage.svg)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Tests Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/tests.yml)](https://github.com/laravel-flatpack/flatpack/actions/workflows/tests.yml)
[![GitHub Code Style Status](https://img.shields.io/github/actions/workflow/status/laravel-flatpack/flatpack/php-cs-fixer.yml?label=code%20style)](https://github.com/laravel-flatpack/flatpack/actions/workflows/php-cs-fixer.yml)

# Flatpack

📦 Administration panel for Laravel, ready to assemble.

- Quickly create a CMS for your Eloquent models.
- Define components with simple and declarative YAML files.
- Build a complete administration panel for your Laravel app in seconds.

📕 [Official Documentation](https://laravel-flatpack.com)

[Flatpack](https://laravel-flatpack.com) makes building fully functional user interfaces for admin panels easier than ever: as easy as editing few lines of a YAML file.

Besides providing a rich set of already built components and a solid stack to build a secure and intuitive experience for the users, it offers a fast and flexible solution for developers who want to have fun, try out new things and save precious time building up the an administration panels.

Flatpack is a reactive full-stack app, built with [TALL stack](https://tallstack.dev/).

![Demo](.github/demo.gif)

---

## Quick Install

Install the package via composer:

```bash
composer require flatpack/flatpack
```

Publish the config file and compiled assets:

```bash
php artisan vendor:publish --tag="flatpack"
```

Note: To make sure that the public assets are always up-to-date, remember to add this command to the `post-update-cmd` list in your `composer.json` file.

```json
  "post-update-cmd": [
      "@php artisan vendor:publish --tag=flatpack"
  ],
```

## Securing your panel

Flatpack gates access with Laravel policies and optional `canAccessFlatpack()` on the authenticated user model (see `config/flatpack.php` under `security.authorization`).

- **Default (`allow_when_policy_missing` = true):** If no policy is registered for a model Flatpack is about to authorize, access is still allowed when the user may access the panel. In **production**, Flatpack logs a warning so you notice missing policies.
- **Strict (`allow_when_policy_missing` = false):** Every Eloquent model exposed in your compositions must have a registered policy (or explicit gate rules). Missing policies deny authorization. Use this once you have full policy coverage and want fail-closed behavior.

**Recommended migration path:** start with the default in development, register policies for each managed model, watch production logs for warnings, then set `FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING=false` (or the config key) when you are confident nothing is missing.

## Usage

Generating Flatpack composition files for `App\Models\Post` model...

```bash
php artisan make:flatpack Post
```

This command will create two files:

- A form template `/flatpack/posts/form.yaml`, that defines the posts form.
- A list template `/flatpack/posts/list.yaml`, that defines the posts table with pagination.

Let's check the result, visit [/backend/posts](http://localhost/backend/posts).

Now start assembling, grab the generated files and map your model's attributes as you need.

## Examples

Defining a form:

```yaml
title: Post
model: App\Models\Post
icon: book-open

toolbar:
    save:
        type: button
        label: Save
        action: save
        style: primary
        shortcut: s

main:
    title:
        label: Post Title
        placeholder: Your Post Title
        type: text

    body:
        type: block-editor

sidebar:
    created_at:
        label: Created
        type: datetime-picker

    updated_at:
        label: Updated
        type: datetime-picker
```

Defining a list:

```yaml
title: Post
model: App\Models\Post
icon: book-open
nav_order: 1

toolbar:
    create:
        label: New Post
        icon: plus
        link: create
        style: primary
        shortcut: enter

columns:
    id:
        label: ID
        sortable: true
        invisible: true

    title:
        label: Title
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

⚙️ You can customise the yaml composition files by mapping your model's attributes, using components of differnt types and features: Data tables, text inputs, rich text editors, date pickers, tag pickers, searchable select menus, image upload, toggles and more.

📖 [Check out the documentation](https://laravel-flatpack.com/reference)

## Current Schema Support (Snapshot)

This section is a quick reference for what is currently supported in Flatpack schema configuration.
It is intended for contributors and package developers and should be updated when new schema types are introduced.

### Supported Form Field Types

Current `type` values for form fields:

- `text`
- `textarea`
- `select`
- `combobox`
- `date-picker`
- `date-range-picker`
- `time-picker`
- `checkbox`
- `switch`
- `rich-text`
- `block-editor`
- `table`

### Supported Table Column Types

Current `type` values for list/table columns:

- `text`
- `select`
- `date`/`datetime`
- `actions`
- `badge`
- `relation`

Relation columns require:

- `relation`
- `relation_name` or `relationName`
- `relation_value` or `relationValue`

### Dashboard Widgets

Dashboard widget schema support is evolving and not yet documented as a stable public contract in this file.
When widget types are finalized, add them here with required and optional keys.

## Requirements

- PHP 8.x
- Composer
- Laravel 9.x

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Fausto Quaggia](https://github.com/faustoq)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
