<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function index(): Response
    {
        return Inertia::render('dashboard');
    }
}
