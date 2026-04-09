<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class FlatpackFormController
{
    public function create(string $entity): Response
    {
        return Inertia::render('form', [
            'entity' => $entity,
            'record' => null,
            'mode' => 'create',
        ]);
    }

    public function edit(string $entity, string $record): Response
    {
        return Inertia::render('form', [
            'entity' => $entity,
            'record' => $record,
            'mode' => 'edit',
        ]);
    }
}
