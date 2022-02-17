---
home: true
heroImage: /flatpack-logo.png
tagline: Administration panel for Laravel, ready to assemble.
actions:
    - text: Quick Start
      link: /#quick-start
      type: primary
    - text: Learn More
      link: /guide/
      type: secondary
features:
    - title: Ready
      details: Flatpack ships with a rich set of pre-built components ready to be assembled.
    - title: Quick
      details: Start generating a yaml configuration file and setup a complete admin UI in seconds.
    - title: Easy
      details: Form validation, relations, custom actions, table search, sorting, and much more.
footer: MIT Licensed | Copyright © 2022-present Fausto Quaggia
---

<div class="video-container">
    <iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/OrxmtDw4pVI" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
</div>

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

Learn how to customize list and form templates.
[Read more](/guide/)
