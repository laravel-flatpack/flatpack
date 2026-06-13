<?php

declare(strict_types=1);

namespace Flatpack\Http\Response;

use Illuminate\Http\JsonResponse;

final class FlatpackErrorPayload
{
    public static function notFound(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'not_found',
                'message' => $message,
            ],
        ], 404);
    }
}
