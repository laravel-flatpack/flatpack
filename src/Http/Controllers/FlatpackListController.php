<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class FlatpackListController
{
    public function index(string $entity): Response
    {
        return Inertia::render('list', [
            'entity' => $entity,
        ]);
    }
}
