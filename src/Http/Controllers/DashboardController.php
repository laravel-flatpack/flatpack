<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(
        private CompositionQuery $compositions,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        /** @var array<string, mixed>|null $schema */
        $schema = $this->compositions->optional(
            Flatpack::dashboardEntity(),
            'list'
        );

        return FlatpackResponse::inertia('dashboard', [
            'schema' => $schema,
        ]);
    }
}
