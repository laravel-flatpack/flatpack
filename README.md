# Laravel Flat Pack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/faustoq/laravel-flatpack.svg?style=flat-square)](https://packagist.org/packages/faustoq/laravel-flatpack)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/faustoq/laravel-flatpack/run-tests?label=tests)](https://github.com/faustoq/laravel-flatpack/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/faustoq/laravel-flatpack/Check%20&%20fix%20styling?label=code%20style)](https://github.com/faustoq/laravel-flatpack/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/faustoq/laravel-flatpack.svg?style=flat-square)](https://packagist.org/packages/faustoq/laravel-flatpack)

📦 Flat pack: Administration panel for Laravel, ready to assemble.

**Yet another Laravel Administration Panel?**

Flat Pack is the quickest and simpliest solution to create fast multi-purpose user interfaces. Out of the box, it provides a rich stack of components, ready to assemble.

Simply create flatpack composition files with `make:flatpack` command and start building!

---

## Installation

You can install the package via composer:

```bash
composer require faustoq/laravel-flatpack
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="config"
```

Publish the compiled assets:

```bash
php artisan vendor:publish --tag="public"
```

## Usage

```bash
php artisan make:flatpack User
```

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
