<?php

/*
 | Configuration file for Flatpack.
 | --------------------------------
 |
 | More infos: https://laravel-flatpack.com/guide/configuration.html
 |
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    |
    | Flatpack form or list actions. You can add your own actions here.
    |
    */
    'actions' => [

        'save' => \Flatpack\Actions\Save::class,

        'upload' => \Flatpack\Actions\Upload::class,

        'delete' => \Flatpack\Actions\Delete::class,

        'restore' => \Flatpack\Actions\Restore::class,

        'empty-trash' => \Flatpack\Actions\EmptyTrash::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | The image and text to be displayed as the top element of the sidebar.
    | Logo should be an absolute url or a relative path to the public directory.
    |
    */
    'brand' => [

        'name' => env('APP_NAME', 'Flatpack'),

        'logo' => env('FLATPACK_LOGO', 'flatpack/images/logo.svg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout settings
    |--------------------------------------------------------------------------
    |
    | Define layout default settings.
    |
     */
    'layout' => [

        'search-box' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation menu
    |--------------------------------------------------------------------------
    |
    | Define custom options for the sidebar menu.
    |
    */
    'navigation' => [],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Flatpack route prefix.
    | Example: http://localhost/backend
    |
    */
    'prefix' => env('FLATPACK_PREFIX', 'backend'),

    /*
    |--------------------------------------------------------------------------
    | Directory
    |--------------------------------------------------------------------------
    |
    | The directory where the Flatpack templates are stored, relative to
    | the application root.
    |
    */
    'directory' => env('FLATPACK_DIRECTORY', 'flatpack'),

    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    |
    | By default, Flatpack will use the default Laravel file storage.
    | You can override this by setting your own file storage.
    |
    */
    'storage' => [

        'disk' => env('FLATPACK_STORAGE_DISK', 'public'),

        'path' => env('FLATPACK_STORAGE_PATH', 'uploads'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models directory
    |--------------------------------------------------------------------------
    |
    | The namespace where Flatpack should find your Eloquent Models.
    |
    */
    'models' => env('FLATPACK_MODELS', "App\\Models"),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to before and/or after all Flatpack routes.
    |
    */
    'middleware' => [

        'before' => [],

        'after' => [],
    ],
];
