<?php

// config for Flatpack

return [

    /**
     * The directory where the Flatpack templates are stored.
     */
    'directory' => 'flatpack',

    /**
     * Flatpack route prefix.
     */
    'prefix' => 'backend',

    /**
     * Middlewares to be applied to all flatpack routes.
     */
    'middleware' => [
        'auth',
        \Faustoq\Flatpack\Http\Middleware\FlatpackMiddleware::class,
    ],

    /**
     * Cache yaml composition templates.
     */
    'cache' => false,

];
