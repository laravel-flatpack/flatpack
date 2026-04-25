<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\DashboardController;
use Flatpack\Http\Controllers\EmbeddedTableColumnRelationOptionsController;
use Flatpack\Http\Controllers\EntityActionController;
use Flatpack\Http\Controllers\FormController;
use Flatpack\Http\Controllers\ListController;
use Flatpack\Http\Controllers\RelationOptionsController;
use Flatpack\Http\Controllers\SessionController;
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
Route::middleware(['auth:' . config('flatpack.guard', 'web'), EnsureFlatpackAccess::class])->group(function () {
    /** Ends the current authenticated Flatpack session. */
    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

    /** Renders the Flatpack dashboard landing page. */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /** Executes a bulk action against selected list records. */
    Route::post('{entity}/bulk', [EntityActionController::class, 'bulkAction'])->name('entities.bulk-action');
    /** Executes a list-level action without a specific record target. */
    Route::post('{entity}/action', [EntityActionController::class, 'listAction'])->name('entities.action');
    /** Executes a row-level action against a specific record. */
    Route::post('{entity}/{record}/action', [EntityActionController::class, 'rowAction'])->name('entities.row-action');
    /** Persists inline edits for a specific list record. */
    Route::patch('{entity}/{record}', [EntityActionController::class, 'updateRecord'])->name('entities.update');

    /** Renders the create form page for an entity. */
    Route::get('{entity}/create', [FormController::class, 'create'])->name('entities.create');
    /** Saves a new record submitted from the create form. */
    Route::post('{entity}', [FormController::class, 'save'])->name('entities.store');

    /** Renders the edit form page for an existing record. */
    Route::get('{entity}/{record}/edit', [FormController::class, 'edit'])->name('entities.edit');
    /** Saves updates submitted from the edit form. */
    Route::patch('{entity}/{record}/save', [FormController::class, 'save'])->name('entities.save');

    /** Returns paginated relation options for remote combobox fields. */
    Route::get('{entity}/relation-options', RelationOptionsController::class)->name('entities.relation-options');
    /** Returns paginated options for a `type: relation` column inside an embedded `type: table` field. */
    Route::get('{entity}/embedded-table-relation-options', EmbeddedTableColumnRelationOptionsController::class)->name('entities.embedded-table-relation-options');

    /** Renders the entity list page with schema-driven records. */
    Route::get('{entity}', [ListController::class, 'index'])->name('entities.index');
});
