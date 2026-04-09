<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class FlatpackDashboardController
{
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }
}
