<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Http\Responses\FlatpackResponse;
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
            config('flatpack.dashboard_entity', 'dashboard'),
            'list'
        );

        return FlatpackResponse::inertia('dashboard', [
            'schema' => $schema,
        ], $request->boolean('json'));
    }
}
