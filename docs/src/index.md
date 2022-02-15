---
home: true
heroImage: /flatpack-logo.png
tagline: Administration panel for Laravel, ready to assemble.
actionText: Quick Start
actionLink: /documentation/
features:
    - title: Ready
      details: Flatpack ships with a rich set of pre-built components ready to be assembled.
    - title: Quick
      details: Start generating a yaml configuration file and setup a complete admin UI in seconds.
    - title: Easy
      details: Form validation, relations, custom actions, table search, sorting, and much more.
---

@[youtube](https://youtu.be/OrxmtDw4pVI)

# Quick start

```bash
# Install the package via composer:
composer require faustoq/laravel-flatpack

# Publish the configuration file:
php artisan vendor:publish --tag="config"

# Publish the compiled assets:
php artisan vendor:publish --tag="public"

# Generate table and form for App\Models\Post model (example)
php artisan make:flatpack Post
```

:tada: Ready! Now visit: [http://localhost/backend](http://localhost/backend)

## Documentation

Learn how to customize table and form templates composition and fields.
[Read more](documentation)

::: slot footer
MIT Licensed | Copyright © 2022-present [Fausto Quaggia](https://github.com/faustoq)
:::
