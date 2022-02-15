# Introduction

Flatpack is probably the quickest and simplest solution to create fast multi-purpose administration panel for your Laravel app.

Out of the box, it provides a rich stack of crafted components, ready to assemble.

![Hej](./assemble.png)

With Flatpack you can:

-   Quickly create CRUD (Create, Read, Update, Delete) interfaces for your [Eloquent models](https://laravel.com/docs/8.x/eloquent).
-   Customise UI and components with simple and declarative **Yaml** files.
-   Build a complete and dynamic administration panel for your site in seconds.

## Installation

Install the package via composer:

```sh
composer require faustoq/laravel-flatpack
```

Publish the configuration file.

```sh
# This command will create `config/flatpack.php` configuration file.

php artisan vendor:publish --tag="config"
```

Publish the compiled assets.

```sh
# This command will copy Flatpack static assets (JS and CSS)
# in `public/flatpack/` directory.

php artisan vendor:publish --tag="public"
```

Flatpack is now ready.

Start by generating the template files for an Eloquent model.

## Usage

In this example, we are going to generate the templates for `App\Models\Post` model.

```sh
php artisan make:flatpack Post
```

This command generates two files into the `/flatpack` directory:

-   A form template `/flatpack/posts/form.yaml`, that defines the composition of your posts form.
-   A list template `/flatpack/posts/list.yaml`, that defines the layout composition of your posts table with pagination.

Let's check the result:

[http://localhost/backend/posts](http://localhost/backend/posts)

Now start assembling, you can [customize](/reference/) the generated `form.yaml` and `list.yaml` by defining the fields and the columns of your views.

Each one will be mapped to a specific pre-made component with different options and capabilities (text editors, date pickers, handling relations, etc.).

Learn more about all the different types of [Form fields](/reference/form-fields) and [List columns](/reference/table-columns) and their options.

**Example** of custom `/flatpack/posts/form.yaml`

```yaml
fields:
    title:
        type: text
        label: Title
        placeholder: Post title
        rules: required|string

    description:
        type: textarea
        label: Description

    created_at:
        type: datetimepicker
        label: Created
        readonly: true
        span: 1

    updated_at:
        type: datetimepicker
        label: Updated
        readonly: true
        span: 1
```

**Example** of custom `/flatpack/posts/list.yaml`

```yaml
columns:
    id:
        label: ID
        sortable: true
        searchable: true

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

**Todo:**

-   Quick Start
-   Overview
    -   Generators
    -   Usage
-   Configuration
    -   Middleware
    -   Authentication
    -   Custom navigation
    -   Custom actions
