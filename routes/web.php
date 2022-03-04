<?php

use Faustoq\Flatpack\Http\Controllers\FormController;
use Faustoq\Flatpack\Http\Controllers\HomeController;
use Faustoq\Flatpack\Http\Controllers\ListController;
use Faustoq\Flatpack\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/{entity}/{id}/upload', [UploadController::class, 'store'])->name('flatpack.upload');
Route::get('/{entity}/{id}', [FormController::class, 'index'])->name('flatpack.form');
Route::get('/{entity}', [ListController::class, 'index'])->name('flatpack.list');
Route::get('/', [HomeController::class, 'index'])->name('flatpack.home');
