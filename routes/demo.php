<?php

declare(strict_types=1);

use Flatpack\Http\Controllers\DemoController;
use Flatpack\Http\Controllers\SchemaController;
use Illuminate\Support\Facades\Route;

if (config('flatpack.enable_demo')) {
    /** Renders the optional component demo page when enabled. */
    Route::get('/demo', [DemoController::class, 'index'])->name('demo.components');
    /** Renders generated docs for `resources/schema/form.json`. */
    Route::get('/schema/form', [SchemaController::class, 'form'])->name('schema.form');
    /** Renders generated docs for `resources/schema/list.json`. */
    Route::get('/schema/list', [SchemaController::class, 'list'])->name('schema.list');
}
