<?php

declare(strict_types=1);

namespace Flatpack\Http\Responses;

use Flatpack\Http\Resources\FlatpackSchema;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FlatpackResponse
{
    public static function inertia(string $view, array $data = [], bool $json = false): Response|JsonResponse
    {
        if ($json) {
            return response()->json(FlatpackSchema::make($data));
        }

        return Inertia::render($view, $data);
    }
}
