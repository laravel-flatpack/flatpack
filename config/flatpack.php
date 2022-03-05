<?php

// config for Flatpack

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

        'create' => \Faustoq\Flatpack\Actions\Create::class,

        'delete' => \Faustoq\Flatpack\Actions\Delete::class,

        'save' => \Faustoq\Flatpack\Actions\Save::class,

        'upload' => \Faustoq\Flatpack\Actions\Upload::class,
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

        'name' => 'Flatpack',

        'logo' => 'flatpack/images/logo.svg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Directory
    |--------------------------------------------------------------------------
    |
    | The directory where the Flatpack templates are stored, relative to
    | the application root.
    |
    */
    'directory' => 'flatpack',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | Flatpack route prefix.
    | Example: http://localhost/backend
    |
    */
    'prefix' => 'backend',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to be applied to all Flatpack routes.
    |
    */
    'middleware' => [

        'before' => [
            // Middleware to be applied before FlatpackMiddleware.
        ],

        'after' => [
            // Middleware to be applied after FlatpackMiddleware.
        ]
    ],



    'storage' => [
        'disk' => 'public',
        'path' => 'uploads',
    ],

];
