<?php

use Faustoq\Flatpack\Http\Controllers\FormController;
use Faustoq\Flatpack\Http\Controllers\HomeController;
use Faustoq\Flatpack\Http\Controllers\ListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/{entity}/{id}', [FormController::class, 'index'])->name('flatpack.form');
    Route::get('/{entity}', [ListController::class, 'index'])->name('flatpack.list');
    Route::get('/', [HomeController::class, 'index'])->name('flatpack.home');
});
