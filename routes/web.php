<?php

declare(strict_types=1);

use Flatpack\Http\Middleware\EnsureFlatpackAccess;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$guard = (string) config('flatpack.guard', 'web');

/*
|--------------------------------------------------------------------------
| Flatpack routes
|--------------------------------------------------------------------------
|
| This file contains the authenticated routes for the Flatpack dashboard.
|
*/
Route::middleware(['auth:' . $guard, EnsureFlatpackAccess::class])->group(function () {
    /** Dashboard route */
    Route::get('/', fn() => Inertia::render('dashboard', []))->name('dashboard');
});

require __DIR__ . '/guest.php';
