<?php

declare(strict_types=1);

namespace Flatpack\Http;

use Flatpack\Support\CompositionDebugLog;
use Flatpack\Support\ModelKeyResolver;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Helpers for Inertia and JSON API responses. Ensures every page receives a
 * `composition_debug` array (empty when {@see config('app.debug')} is false).
 *
 * When {@see config('app.debug')} is true, log context matches the composition file path:
 * `{entity}/form.yaml` (form), `{entity}/list.yaml` (list), `{dashboard_entity}/list.yaml` (dashboard).
 * Schema props are sanitized (unknown top-level keys, form field rules) in line with that context.
 */
final class FlatpackResponse
{
    /**
     * Returns a log only when {@see config('app.debug')} is true. Used internally and in tests.
     */
    public static function compositionDebugLog(string $context): ?CompositionDebugLog
    {
        return config('app.debug') ? new CompositionDebugLog($context) : null;
    }

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
        ?CompositionDebugLog $compositionDebugLog = null,
    ): Response|JsonResponse {
        $data = self::prepareInertiaData($view, $data, $compositionDebugLog);

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
        ?CompositionDebugLog $compositionDebugLog = null,
    ): array {
        $log = $compositionDebugLog ?? self::compositionDebugLogForView($view, $data);

        $data = self::appendModelMetadata($view, $data);

        return self::applyCompositionDebug($data, $log);
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
        $directModel = self::normalizeModelClassName($data['model'] ?? null);
        if ($directModel !== null) {
            return $directModel;
        }

        $schema = $data['schema'] ?? null;
        if (! is_array($schema)) {
            return null;
        }

        return self::normalizeModelClassName($schema['model'] ?? null);
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
     */
    private static function compositionDebugLogForView(string $view, array $data): ?CompositionDebugLog
    {
        $context = self::inferCompositionDebugContext($view, $data);

        return $context !== null ? self::compositionDebugLog($context) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function inferCompositionDebugContext(string $view, array $data): ?string
    {
        return match ($view) {
            'form' => self::contextForEntityComposition($data, 'form.yaml'),
            'list' => self::contextForEntityComposition($data, 'list.yaml'),
            'dashboard' => self::contextForDashboardComposition(),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function contextForEntityComposition(array $data, string $fileName): ?string
    {
        $entity = $data['entity'] ?? null;
        if (! is_string($entity) || $entity === '') {
            return null;
        }

        return self::compositionDebugContextForEntity($entity, $fileName);
    }

    private static function contextForDashboardComposition(): string
    {
        return self::compositionDebugContextForDashboard();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function applyCompositionDebug(array $data, ?CompositionDebugLog $compositionDebug): array
    {
        if ($compositionDebug !== null) {
            $data['composition_debug'] = $compositionDebug->all();
        } elseif (! array_key_exists('composition_debug', $data)) {
            $data['composition_debug'] = [];
        }

        return $data;
    }
}
