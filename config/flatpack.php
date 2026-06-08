<?php

declare(strict_types=1);

use Flatpack\Actions\Handlers\BulkDeleteHandler;
use Flatpack\Actions\Handlers\BulkForceDeleteHandler;
use Flatpack\Actions\Handlers\BulkRestoreHandler;
use Flatpack\Actions\Handlers\CreateRecordHandler;
use Flatpack\Actions\Handlers\DeleteRecordHandler;
use Flatpack\Actions\Handlers\EditRecordHandler;
use Flatpack\Actions\Handlers\ForceDeleteRecordHandler;
use Flatpack\Actions\Handlers\ReorderActionHandler;
use Flatpack\Actions\Handlers\RestoreRecordHandler;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Http\Controllers\SessionController;

return [
    /*
    |--------------------------------------------------------------------------
    | Composition (YAML entities)
    |--------------------------------------------------------------------------
    |
    | Directory containing one folder per entity (`form.yaml`, `list.yaml`).
    | Override with FLATPACK_COMPOSITION_PATH (absolute path or path relative to
    | the application root, as resolved by your host).
    |
    */
    'composition' => [
        'path' => env('FLATPACK_COMPOSITION_PATH', base_path('flatpack')),
        'dashboard_entity' => env('FLATPACK_COMPOSITION_DASHBOARD_ENTITY', 'dashboard'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP (routing surface)
    |--------------------------------------------------------------------------
    |
    | URL prefix for all Flatpack dashboard routes, middleware stack for the route
    | group, login POST target, and optional JSON exception-handler registration
    | for redirecting unauthenticated Inertia/XHR requests to the login route.
    |
    */
    'http' => [
        'prefix' => env('FLATPACK_HTTP_PREFIX', 'flatpack'),
        'middleware' => ['web'],
        'login' => [
            /*
            | "store" handles credentials (session auth by default).
            | Point to Fortify's AuthenticatedSessionController@store
            | or any [Controller::class, 'method'].
            */
            'store' => [SessionController::class, 'store'],
            'throttle' => env('FLATPACK_HTTP_LOGIN_THROTTLE', 'throttle:5,1'),
        ],
        'register_json_exception_handler' => env('FLATPACK_HTTP_REGISTER_JSON_EXCEPTION_HANDLER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Authentication and authorization settings.
    |
    */
    'security' => [
        /*
        | Guard name for auth / guest middleware (e.g. web, sanctum).
        */
        'guard' => env('FLATPACK_SECURITY_GUARD', 'web'),

        'authorization' => [
            /*
            | Allow panel users to act on models with no registered policy.
            | Default: true when APP_ENV=local, false otherwise.
            | Override: FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING.
            | Logs a warning in production when true.
            */
            'allow_when_policy_missing' => env(
                'FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING',
                env('APP_ENV', 'production') === 'local',
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Record & row actions
    |--------------------------------------------------------------------------
    |
    | Handler map for form submit and row/list actions.
    | Form actions and row actions must include `action` matching a key here.
    | Implements Flatpack\Contracts\Actions\FlatpackAction.
    |
    */
    'actions' => [
        'create' => CreateRecordHandler::class,
        'edit' => EditRecordHandler::class,
        'save' => SaveRecordHandler::class,
        'delete' => DeleteRecordHandler::class,
        'restore' => RestoreRecordHandler::class,
        'force_delete' => ForceDeleteRecordHandler::class,
        'reorder' => ReorderActionHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | List bulk actions
    |--------------------------------------------------------------------------
    |
    | Handlers for POST bulk operations on selected rows.
    | List bulkactions must include `action` matching a key here.
    | Implements Flatpack\Contracts\Actions\FlatpackBulkAction.
    |
    */
    'bulk_actions' => [
        'delete' => BulkDeleteHandler::class,
        'restore' => BulkRestoreHandler::class,
        'force_delete' => BulkForceDeleteHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget data providers
    |--------------------------------------------------------------------------
    |
    | Provider map for dashboard widgets that require backend data resolution.
    | Widgets in list.yaml/form.yaml reference these keys via `provider`.
    |
    | Must implement Flatpack\Widgets\Contracts\WidgetDataProvider; handle() must return
    | a Flatpack\Widgets\Data\WidgetPayload (Arrayable + Jsonable with inline toJson()).
    |
    | Example:
    | 'total_revenue' => \App\Flatpack\Widgets\TotalRevenueWidget::class,
    | 'health_check' => \App\Flatpack\Widgets\HealthCheckWidget::class,
    |
    */
    'widget_providers' => [],

    /*
    |--------------------------------------------------------------------------
    | Entity list pagination
    |--------------------------------------------------------------------------
    |
    | Defaults for list pages and the upper bound for ?per_page= on list routes.
    |
    */
    'lists' => [
        'per_page' => (int) env('FLATPACK_LISTS_PER_PAGE', 10),
        'max_per_page' => (int) env('FLATPACK_LISTS_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Forms (relation-backed table fields)
    |--------------------------------------------------------------------------
    |
    | Limits for hydrating related rows on embedded `type: table` fields.
    | Per-field YAML `limit` may reduce further (never above hard_max_rows).
    |
    */
    'forms' => [
        'enforce_relation_table_limit' => env('FLATPACK_FORMS_ENFORCE_RELATION_TABLE_LIMIT', true),
        'relation_table_default_limit' => (int) env('FLATPACK_FORMS_RELATION_TABLE_DEFAULT_LIMIT', 100),
        'relation_table_hard_max_rows' => (int) env('FLATPACK_FORMS_RELATION_TABLE_HARD_MAX', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    |
    |
    | Disks: omitted YAML `disk` uses `file_disk` for every mode (url / image / file / relation).
    | Use YAML `disk: media` to store on `media_disk` (not a Laravel disk named `media`).
    | Env: `FLATPACK_UPLOADS_*_DISK`, then `FILESYSTEM_DISK`, then `local`; disks live in `config/filesystems.php`.
    |
    */
    'uploads' => [
        'file_disk' => env('FLATPACK_UPLOADS_FILE_DISK', env('FILESYSTEM_DISK', 'local')),
        'media_disk' => env('FLATPACK_UPLOADS_MEDIA_DISK', env('FILESYSTEM_DISK', 'local')),

        'media_model' => env('FLATPACK_UPLOADS_MEDIA_MODEL'),

        /*
         | Limits: `max_size_kb` and `max_files` cap AJAX uploads (effective = min(YAML, config);
         | YAML omitted uses config alone). The React field uses YAML for client hints; the server
         | always enforces these caps.
         */
        'max_size_kb' => (int) env('FLATPACK_UPLOADS_MAX_SIZE_KB', 10240),
        'max_files' => (int) env('FLATPACK_UPLOADS_MAX_FILES', 10),

        /*
        | TTL for signed URLs used when files are not web-addressable (private disk / visibility).
        | Each form load regenerates hydration URLs; increase if embeds must stay valid longer.
        */
        'signed_url_ttl_minutes' => (int) env('FLATPACK_UPLOADS_SIGNED_URL_TTL_MINUTES', 10_080),

    ],

    /*
    |--------------------------------------------------------------------------
    | UI / shell
    |--------------------------------------------------------------------------
    |
    | Presentation toggles and sidebar overrides. When each navigation override
    | (`main`, `secondary`, `bottom`) is null, Flatpack derives that group from
    | compositions (lists use optional `menu` for placement).
    |
    */
    'ui' => [
        'allow_external_navigation_urls' => env('FLATPACK_UI_ALLOW_EXTERNAL_NAVIGATION_URLS', false),
        'show_action_shortcut_hints' => env('FLATPACK_UI_SHOW_ACTION_SHORTCUT_HINTS', false),
        /*
        | Sidebar navigation menu items. Here you can entirely override the default
        | navigation menu items derived from the filesystem.
        */
        'navigation' => [
            'main' => null,
            'secondary' => null,
            'bottom' => null,
        ],
        // Quick action menu item. Visible at the top of the sidebar.
        'quick_action' => null,
        // Logo SVG icon URL. Supports theme colors with SVG masks.
        'logo' => '/vendor/flatpack/logo.svg',
        // Name to display in the sidebar.
        'name' => 'Flatpack',
        // Themes
        'themes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled assets
    |--------------------------------------------------------------------------
    |
    | When true and the host lacks vendor/flatpack/build/manifest.json, Flatpack
    | copies the committed package build into public/vendor/flatpack/build on boot.
    |
    */
    'assets' => [
        'sync_from_package' => env('FLATPACK_ASSETS_SYNC_FROM_PACKAGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Optional package features (demo catalog route, etc.).
    |
    */
    'features' => [
        'demo' => env('FLATPACK_FEATURES_DEMO', false),
    ],
];
