<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get(
    '/demo',
    fn(Request $request) => Inertia::render('demo/components', [
        'query' => $request->query(),
    ])
)->name('demo.components');
