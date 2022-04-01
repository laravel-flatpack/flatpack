<?php

use Flatpack\Http\Controllers\FormController;
use Flatpack\Http\Controllers\HomeController;
use Flatpack\Http\Controllers\ListController;
use Flatpack\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/{entity}/{id}/upload', [UploadController::class, 'store'])->name('flatpack.upload');
Route::get('/{entity}/{id}', [FormController::class, 'index'])->name('flatpack.form');
Route::get('/{entity}', [ListController::class, 'index'])->name('flatpack.list');
Route::get('/', [HomeController::class, 'index'])->name('flatpack.home');
