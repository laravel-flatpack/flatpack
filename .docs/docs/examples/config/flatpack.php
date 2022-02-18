<?php

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
    | Example: http://example.com/backend
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

        // Middleware to be applied before FlatpackMiddleware.

        'before' => [
            'web',
        ],

        // Middleware to be applied after FlatpackMiddleware.

        'after' => [
            //
        ]
    ],

];
