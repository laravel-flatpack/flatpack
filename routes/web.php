<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\FlatpackDashboardController;
use Flatpack\Http\Controllers\FlatpackFormController;
use Flatpack\Http\Controllers\FlatpackListController;
use Flatpack\Http\Middleware\EnsureFlatpackAccess;
use Illuminate\Support\Facades\Route;

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
    /** Dashboard route */
    Route::get('/', [FlatpackDashboardController::class, 'index'])->name('dashboard');

    /** Entity list route */
    Route::get('{entity}', [FlatpackListController::class, 'index'])->name('entities.index');

    /** Entity create form route */
    Route::get('{entity}/create', [FlatpackFormController::class, 'create'])->name('entities.create');

    /** Entity edit form route */
    Route::get('{entity}/{record}/edit', [FlatpackFormController::class, 'edit'])->name('entities.edit');
});
