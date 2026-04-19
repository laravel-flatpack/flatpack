<?php

declare(strict_types=1);

use Flatpack\Actions\Handlers\BulkDeleteHandler;
use Flatpack\Actions\Handlers\CreateRecordHandler;
use Flatpack\Actions\Handlers\DeleteRecordHandler;
use Flatpack\Actions\Handlers\EditRecordHandler;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Http\Controllers\SessionController;

return [
    /*
    |--------------------------------------------------------------------------
    | Composition files path
    |--------------------------------------------------------------------------
    |
    | Directory containing entity folders (each with form.yaml / list.yaml).
    |
    */
    'path' => base_path('flatpack'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard routes prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for the Flatpack dashboard routes. Default: 'flatpack'.
    |
    */
    'prefix' => env('FLATPACK_PREFIX', 'flatpack'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard entity name
    |--------------------------------------------------------------------------
    |
    | The entity name for the dashboard page. Default: 'dashboard'.
    | This is the name of the folder in the flatpack path that contains the
    | dashboard list.yaml file.
    |
    */
    'dashboard_entity' => env('FLATPACK_DASHBOARD_ENTITY', 'dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Entity list pagination
    |--------------------------------------------------------------------------
    |
    | Default page size and upper bound for ?per_page= on list routes.
    |
    */
    'list' => [
        'per_page' => 10,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Form configuration
    |--------------------------------------------------------------------------
    |
    | Disable header actions until the form is dirty.
    |
    */
    'forms' => [
        'disable_actions_until_dirty' => false,

        /*
        |--------------------------------------------------------------------------
        | Relation-backed table fields (`type: table` + `relation`)
        |--------------------------------------------------------------------------
        |
        | Caps how many related rows are hydrated on edit to avoid huge payloads.
        | Per-field YAML `limit` may further reduce the count (never exceed hard_max_rows).
        |
        */
        'enforce_relation_table_limit' => env('FLATPACK_ENFORCE_RELATION_TABLE_LIMIT', true),
        'relation_table_default_limit' => (int) env('FLATPACK_RELATION_TABLE_DEFAULT_LIMIT', 100),
        'relation_table_hard_max_rows' => (int) env('FLATPACK_RELATION_TABLE_HARD_MAX', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flatpack actions
    |--------------------------------------------------------------------------
    |
    | The actions for forms and lists. Default: ['save', 'delete'].
    | You can add your own actions by adding a class that implements
    | the Flatpack\Contracts\Actions\FlatpackAction interface.
    |
    */
    'actions' => [
        'create' => CreateRecordHandler::class,
        'edit' => EditRecordHandler::class,
        'save' => SaveRecordHandler::class,
        'delete' => DeleteRecordHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Flatpack bulk actions
    |--------------------------------------------------------------------------
    |
    | Handlers for list-level bulk actions. Override these classes to customize
    | behavior (for example, soft-delete policy, auditing, queues, etc).
    | You can add your own bulk actions by adding a class that implements
    | the Flatpack\Contracts\Actions\FlatpackBulkAction interface.
    |
    */
    'bulk_actions' => [
        'delete' => BulkDeleteHandler::class,
    ],

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
    | Login route configuration
    |--------------------------------------------------------------------------
    |
    | "store" is the action that processes credentials. By default Flatpack uses
    | session authentication (Auth::attempt). Point this to Fortify's
    | AuthenticatedSessionController@store if you use Fortify, or any invokable
    | [Controller::class, 'method'] your app provides.
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
    | Dashboard UI
    |--------------------------------------------------------------------------
    |
    | Controls shell chrome behavior. When show_action_shortcut_hints is false
    | (default), shortcut hints are hidden on header action buttons; shortcuts
    | remain active and appear in the Keyboard shortcuts dialog from the user menu.
    |
    */
    'ui' => [
        'show_action_shortcut_hints' => env('FLATPACK_SHOW_ACTION_SHORTCUT_HINTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation URL policy
    |--------------------------------------------------------------------------
    |
    | Controls whether menu/action links may point to external origins.
    | Unsafe URL schemes like javascript:, data:, and protocol-relative URLs
    | are always rejected.
    |
    */
    'navigation' => [
        'allow_external_origins' => env('FLATPACK_ALLOW_EXTERNAL_ORIGINS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quick action
    |--------------------------------------------------------------------------
    |
    | You can define the quick action button at the top of the sidebar.
    | Set it to null to disable it.
    |
    | Example:
    | 'quick_action' => [
    |     'name' => 'Create Post',
    |     'url' => '/flatpack/posts/create',
    |     'icon' => 'plus'
    | ],
    |
    */
    'quick_action' => null,

    /*
    |--------------------------------------------------------------------------
    | Menu override
    |--------------------------------------------------------------------------
    |
    | Set to null to use filesystem-derived menu.
    |
    | When set, replaces filesystem-derived menu.
    | Example:
    | 'menu' => [
    |     ['name' => 'Page Name', 'url' => '/path/to/page', 'icon' => 'book'],
    |     ['name' => 'Page Name 2', 'url' => '/path/to/page-2', 'icon' => 'folder'],
    | ],
    |
    */
    'menu' => null,

    /*
    |--------------------------------------------------------------------------
    | Secondary menu
    |--------------------------------------------------------------------------
    |
    | You can define a secondary menu with a label and a list of items.
    | This menu is displayed in the sidebar below the main menu.
    |
    | Example:
    | 'secondary_menu' => [
    |     'label' => 'Secondary Menu',
    |     'items' => [
    |         ['name' => 'Item 1', 'url' => '/path/to/page-1', 'icon' => 'book'],
    |         ['name' => 'Item 2', 'url' => '/path/to/page-2', 'icon' => 'folder'],
    |     ],
    | ],
    |
    */
    'secondary_menu' => null,

    /*
    |--------------------------------------------------------------------------
    | Bottom menu
    |--------------------------------------------------------------------------
    |
    | You can define a bottom menu with a list of items.
    | This menu is displayed in the sidebar at the bottom.
    |
    | Example:
    | 'bottom_menu' => [
    |     'label' => 'Settings',
    |     'items' => [
    |         ['name' => 'Item 1', 'url' => '/path/to/page-1', 'icon' => 'settings'],
    |         ['name' => 'Item 2', 'url' => '/path/to/page-2', 'icon' => 'user'],
    |     ],
    | ],
    |
    */
    'bottom_menu' => null,

    /*
    |--------------------------------------------------------------------------
    | Enable demo page
    |--------------------------------------------------------------------------
    |
    | Whether to enable the demo page. Default: false.
    | The demo page displays all the components and their props in a table.
    |
    */
    'enable_demo' => env('FLATPACK_ENABLE_DEMO', false),
];
