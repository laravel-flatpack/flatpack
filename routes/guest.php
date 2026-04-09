<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\FlatpackSessionController;
use Illuminate\Support\Facades\Route;

$loginStore = config('flatpack.login.store');
$throttle = config('flatpack.login.throttle');

/*
|--------------------------------------------------------------------------
| Flatpack guest routes
|--------------------------------------------------------------------------
|
| This file contains the guest routes for the Flatpack dashboard,
| such as the login route and the login store route.
|
*/
Route::middleware('guest:' . config('flatpack.guard', 'web'))->group(function () use ($loginStore, $throttle) {
    /** Login route */
    Route::get('login', [FlatpackSessionController::class, 'create'])->name('login');

    $route = Route::post('login', $loginStore);

    if (is_string($throttle) && $throttle !== '') {
        $route->middleware($throttle);
    }

    $route->name('login.store');
});
