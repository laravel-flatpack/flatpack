<?php

declare(strict_types=1);

namespace Flatpack\Http;

use Flatpack\Support\CompositionDebugContext;
use Flatpack\Support\ModelKeyResolver;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Helpers for Inertia and JSON API responses. Ensures every page receives a
 * `composition_debug` array (empty when {@see config('app.debug')} is false or no messages were recorded).
 *
 * When {@see config('app.debug')} is true, log context matches the composition file path:
 * `{entity}/form.yaml` (form), `{entity}/list.yaml` (list), `{dashboard_entity}/list.yaml` (dashboard).
 */
final class FlatpackResponse
{
    public static function compositionDebugContextForEntity(string $entity, string $fileName): string
    {
        $configuredPath = (string) config('flatpack.composition.path', 'flatpack');
        $normalizedPath = str_replace('\\', '/', $configuredPath);
        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/'))));
        $basePath = $segments !== [] ? end($segments) : 'flatpack';

        return ($basePath !== '' ? $basePath . '/' : '') . trim($entity, '/') . '/' . ltrim($fileName, '/');
    }

    public static function compositionDebugContextForDashboard(): string
    {
        $slug = trim((string) config('flatpack.composition.dashboard_entity', 'dashboard'));

        return self::compositionDebugContextForEntity($slug !== '' ? $slug : 'dashboard', 'list.yaml');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function inertia(
        string $view,
        array $data = [],
    ): Response|JsonResponse {
        $data = self::prepareInertiaData($view, $data);

        if (request()->boolean('json')) {
            return response()->json($data);
        }

        return Inertia::render($view, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prepareInertiaData(
        string $view,
        array $data,
    ): array {
        $data = self::appendModelMetadata($view, $data);

        return self::applyCompositionDebugFromRequest($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function appendModelMetadata(string $view, array $data): array
    {
        if (! in_array($view, ['form', 'list', 'dashboard'], true)) {
            return $data;
        }

        $resolvedModel = self::resolveModelClassForView($data);
        $data['model'] = $resolvedModel;
        $data['model_key'] = app(ModelKeyResolver::class)->resolve($resolvedModel);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveModelClassForView(array $data): ?string
    {
        return self::normalizeModelClassName($data['model'] ?? null);
    }

    private static function normalizeModelClassName(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $modelClass = trim($candidate);

        return $modelClass !== '' ? $modelClass : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function applyCompositionDebugFromRequest(array $data): array
    {
        $lines = app(CompositionDebugContext::class)->lines();
        if ($lines !== []) {
            $data['composition_debug'] = $lines;
        } elseif (! array_key_exists('composition_debug', $data)) {
            $data['composition_debug'] = [];
        }

        return $data;
    }
}
