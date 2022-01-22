<?php

// config for Flatpack

return [

    /**
     * Flatpack route prefix.
     */
    'prefix' => 'backend',

    /**
     * The directory where the Flatpack templates are stored.
     */
    'directory' => 'flatpack',

    /**
     * Middleware to be applied to all routes.
     */
    'middleware' => \Faustoq\Flatpack\Http\Middleware\FlatpackMiddleware::class,

];
