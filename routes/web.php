<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\DashboardController;
use Flatpack\Http\Controllers\FormController;
use Flatpack\Http\Controllers\ListController;
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
    /** Logout route */
    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

    /** Dashboard route */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /** Entity bulk actions route */
    Route::post('{entity}/bulk', [ListController::class, 'bulkAction'])->name('entities.bulk-action');

    /** Entity list route */
    Route::get('{entity}', [ListController::class, 'index'])->name('entities.index');

    /** Entity create form route */
    Route::get('{entity}/create', [FormController::class, 'create'])->name('entities.create');

    /** Entity edit form route */
    Route::get('{entity}/{record}/edit', [FormController::class, 'edit'])->name('entities.edit');
});
