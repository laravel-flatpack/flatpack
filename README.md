![Image of package](.github/package-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/faustoq/laravel-flatpack.svg?style=flat-square)](https://packagist.org/packages/faustoq/laravel-flatpack)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/faustoq/laravel-flatpack/run-tests?label=tests)](https://github.com/faustoq/laravel-flatpack/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/faustoq/laravel-flatpack/Check%20&%20fix%20styling?label=code%20style)](https://github.com/faustoq/laravel-flatpack/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/faustoq/laravel-flatpack.svg?style=flat-square)](https://packagist.org/packages/faustoq/laravel-flatpack)

# Flatpack

📦 Flatpack: Administration panel for Laravel, ready to assemble.

-   Quickly create CRUD (Create, Read, Update, Delete) interfaces for your Eloquent models.
-   Customise UI and components with simple and declarative YAML files.
-   Build a complete and dynamic administration panel for your site in seconds.

📕 [Documentation](https://laravel-flatpack.com)

---

## Quick Install

You can install the package via composer:

```bash
composer require faustoq/laravel-flatpack
```

Publish the config file and compiled assets:

```bash
php artisan vendor:publish --tag="flatpack"
```

## Usage

Generate the flatpack composition files for App\Models\Post model...

```bash
php artisan make:flatpack Post
```

This command will create two files:

-   A form template `/flatpack/posts/form.yaml`, that defines the layout composition of your posts form.
-   A list template `/flatpack/posts/list.yaml`, that defines the layout composition of your posts table with pagination.

Let's check the result, visit `/backend/posts`.

Now start assembling, grab the generated files and define how they should look!

## Examples

-   [Form example](#form)
-   [List example](#list)

### Form

Example of generated file `/flatpack/posts/form.yaml`

```yaml
fields:
    name:
        label: Name
        type: text

    description:
        label: Description
        type: textarea

    created_at:
        label: Created
        type: datetime-picker

    updated_at:
        label: Updated
        type: datetime-picker
```

### List

Example of generated `/flatpack/posts/list.yaml`

```yaml
columns:
    id:
        label: ID
        sortable: true
        searchable: true
    name:
        label: Name
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

You can edit the yaml composition files by mapping your model's attributes with components of differnt types and features.

[Check out the Documentation](https://laravel-flatpack.com/reference)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Fausto Quaggia](https://github.com/faustoq)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
