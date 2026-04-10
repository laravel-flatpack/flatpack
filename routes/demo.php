<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get(
    'demo/components',
    fn (Request $request) => Inertia::render('demo/components', [
        'type' => $request->query('type') !== null && $request->query('type') !== ''
            ? (string) $request->query('type')
            : null,
        'multiple' => $request->boolean('multiple'),
    ])
)->name('demo.components');
