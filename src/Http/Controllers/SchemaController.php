<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\SchemaDocumentationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final readonly class SchemaController
{
    public function __construct(
        private SchemaDocumentationPresenter $presenter,
    ) {}

    public function form(Request $request): Response|JsonResponse
    {
        return FlatpackResponse::inertia('demo/schema', [
            'schemaType' => 'form',
            'query' => $request->query(),
            'document' => $this->presenter->buildDocument('form'),
        ]);
    }

    public function list(Request $request): Response|JsonResponse
    {
        return FlatpackResponse::inertia('demo/schema', [
            'schemaType' => 'list',
            'query' => $request->query(),
            'document' => $this->presenter->buildDocument('list'),
        ]);
    }
}
