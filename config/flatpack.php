<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\SessionController;

return [
    /*
    |--------------------------------------------------------------------------
    | Composition path
    |--------------------------------------------------------------------------
    |
    | Directory containing entity folders (each with form.yaml / list.yaml).
    |
    */
    'path' => base_path('flatpack'),

    /*
    |--------------------------------------------------------------------------
    | Route prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => 'flatpack',

    /*
    |--------------------------------------------------------------------------
    | Authentication guard
    |--------------------------------------------------------------------------
    |
    | Used for Flatpack "guest" / "auth" route middleware (e.g. guest:web, auth:web).
    |
    */
    'guard' => env('FLATPACK_AUTH_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Login (POST /{prefix}/login)
    |--------------------------------------------------------------------------
    |
    | "store" is the action that processes credentials. By default Flatpack uses
    | session authentication (Auth::attempt). Point this to Fortify's
    | AuthenticatedSessionController@store if you use Fortify, or any invokable
    | [Controller::class, 'method'] your app provides.
    |
    | Example (Fortify):
    | 'store' => [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'],
    |
    */
    'login' => [
        'store' => [SessionController::class, 'store'],
        'throttle' => env('FLATPACK_LOGIN_THROTTLE', 'throttle:5,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Unauthenticated Inertia / XHR behaviour
    |--------------------------------------------------------------------------
    |
    | When true, registers exception-handler rules so AuthenticationException on
    | Flatpack routes yields an HTML redirect (not JSON 401 / empty 401): a
    | shouldRenderJsonWhen callback plus a renderable redirect to flatpack.login.
    | Disable if you customize these yourself and merge Flatpack rules.
    |
    */
    'register_json_exception_handler' => env('FLATPACK_REGISTER_JSON_EXCEPTION_HANDLER', true),

    /*
    |--------------------------------------------------------------------------
    | Vite compiled assets
    |--------------------------------------------------------------------------
    |
    | After `cd flatpack-package && npm install && npm run build`, the manifest
    | lives at public/vendor/flatpack/build on the host. The build script also
    | copies output into flatpack-package/public/build so you can commit it;
    | on boot, that copy is synced to the host when the manifest is missing.
    |
    | The host app should override Inertia's HandleInertiaRequests::rootView() to
    | return `flatpack::app` when FlatpackRequest::matches($request), so the
    | Blade layout uses @vite(..., 'vendor/flatpack/build') (middleware order alone
    | is not reliable).
    |
    */
    'sync_compiled_assets_from_package' => env('FLATPACK_SYNC_COMPILED_ASSETS', true),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Applied to all Flatpack HTTP routes. Add auth middleware in the host app.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Menu override
    |--------------------------------------------------------------------------
    |
    | When non-empty, replaces filesystem-derived menu. Each item:
    | 'slug' => ['name' => '', 'route' => '', 'icon' => '']
    |
    */
    'menu' => [],
];
