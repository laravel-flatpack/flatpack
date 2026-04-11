<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/demo', [DemoController::class, 'index'])->name('demo.components');
