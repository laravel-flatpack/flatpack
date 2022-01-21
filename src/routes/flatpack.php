<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('flatpack.prefix', 'flatpack'))->group(function () {
    Route::get('/', function () {
        return 'homepage';
    })->name('flatpack.home');
});
