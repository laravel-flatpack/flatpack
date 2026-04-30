<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\DashboardController;
use Flatpack\Http\Controllers\EntityActionController;
use Flatpack\Http\Controllers\FormController;
use Flatpack\Http\Controllers\ListController;
use Flatpack\Http\Controllers\RelationOptionsController;
use Flatpack\Http\Controllers\SessionController;
use Flatpack\Http\Controllers\TableRowController;
use Flatpack\Http\Middleware\EnsureFlatpackAccess;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/demo.php';
require __DIR__ . '/guest.php';

/*
|--------------------------------------------------------------------------
| Flatpack routes
|--------------------------------------------------------------------------
|
| This file contains the authenticated routes for the Flatpack dashboard.
|
*/
Route::middleware(['auth:' . config('flatpack.security.guard', 'web'), EnsureFlatpackAccess::class])->group(function () {
    /** Ends the current authenticated Flatpack session. */
    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

    /** Renders the Flatpack dashboard landing page. */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    /** Persists row drawer edits for model-backed dashboard table widgets. */
    Route::patch('dashboard/widgets/{widget}/{record}', [TableRowController::class, 'updateDashboardWidgetRow'])->name('dashboard.widgets.update-row');
    /** Executes bulk action for model-backed dashboard table widgets. */
    Route::post('dashboard/widgets/{widget}/bulk', [TableRowController::class, 'bulkDashboardWidgetRows'])->name('dashboard.widgets.bulk-action');

    /** Executes a bulk action against selected list records. */
    Route::post('{entity}/bulk', [EntityActionController::class, 'bulkAction'])->name('entities.bulk-action');
    /** Executes a list-level action without a specific record target. */
    Route::post('{entity}/action', [EntityActionController::class, 'listAction'])->name('entities.action');
    /** Executes a row-level action against a specific record. */
    Route::post('{entity}/{record}/action', [EntityActionController::class, 'rowAction'])->name('entities.row-action');
    /** Persists drag-and-drop record ordering in one request. */
    Route::patch('{entity}/{record}/reorder', [EntityActionController::class, 'reorderRecord'])->name('entities.row-reorder');
    /** Persists inline edits for a specific list record. */
    Route::patch('{entity}/{record}', [EntityActionController::class, 'updateRecord'])->name('entities.update');
    /** Persists row drawer edits for model-backed form table fields. */
    Route::patch('{entity}/table-fields/{field}/{record}', [TableRowController::class, 'updateFormTableRow'])->name('entities.table-fields.update-row');

    /** Renders the create form page for an entity. */
    Route::get('{entity}/create', [FormController::class, 'create'])->name('entities.create');
    /** Submits the form (create or update); pass {@code record} in the body for edits. */
    Route::post('{entity}/submit', [FormController::class, 'submit'])->name('entities.form.submit');

    /** Renders the edit form page for an existing record. */
    Route::get('{entity}/{record}/edit', [FormController::class, 'edit'])->name('entities.edit');

    /** Returns paginated relation options for remote `type: combobox` fields. */
    Route::get('{entity}/relation-options', [RelationOptionsController::class, 'field'])->name('entities.relation-options');
    /** Returns paginated options for a `type: relation` column inside an embedded `type: table` field. */
    Route::get('{entity}/embedded-table-relation-options', [RelationOptionsController::class, 'embeddedTableColumn'])->name('entities.embedded-table-relation-options');

    /** Renders the entity list page with schema-driven records. */
    Route::get('{entity}', [ListController::class, 'index'])->name('entities.index');
});
