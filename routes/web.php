<?php

use Flatpack\Http\Controllers\Auth\AuthenticatedSessionController;
use Flatpack\Http\Controllers\FormController;
use Flatpack\Http\Controllers\HomeController;
use Flatpack\Http\Controllers\ListController;
use Flatpack\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::name('flatpack.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.action');
    });

    Route::middleware('flatpack-auth')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::middleware('flatpack')->group(function () {
            Route::post('/{entity}/{id}/upload', [UploadController::class, 'store'])->name('upload');
            Route::get('/{entity}/{id}', [FormController::class, 'index'])->name('form');
            Route::get('/{entity}', [ListController::class, 'index'])->name('list');
            Route::get('/', [HomeController::class, 'index'])->name('home');
        });
    });
});
