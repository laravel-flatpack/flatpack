<?php

// config for Flatpack

return [

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

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    |
    | Flatpack form or list actions.
    |
    */
    'actions' => [

        'create' => \Faustoq\Flatpack\Actions\Create::class,

        'save' => \Faustoq\Flatpack\Actions\Save::class,

        'delete' => \Faustoq\Flatpack\Actions\Delete::class,
    ]

];
