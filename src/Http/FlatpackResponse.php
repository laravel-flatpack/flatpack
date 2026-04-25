<?php

declare(strict_types=1);

namespace Flatpack\Http;

use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\Lists\ListSchemaNormalizer;
use Flatpack\Support\CompositionDebugLog;
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

    /**
     * @param  array<string, mixed>  $data
     */
    public static function inertia(
        string $view,
        array $data = [],
        bool $json = false,
    ): Response|JsonResponse {
        $data = self::prepareInertiaData($view, $data);

        if ($json) {
            return response()->json($data);
        }

        return Inertia::render($view, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prepareInertiaData(string $view, array $data): array
    {
        $externalLog = isset($data['composition_debug_log']) && $data['composition_debug_log'] instanceof CompositionDebugLog
            ? $data['composition_debug_log']
            : null;
        unset($data['composition_debug_log']);

        $skipFormSchemaNormalize = ($data['_flatpack_skip_form_schema_normalize'] ?? false) === true;
        unset($data['_flatpack_skip_form_schema_normalize']);

        $log = $externalLog ?? self::compositionDebugLogForView($view, $data);

        if ($view === 'form' && array_key_exists('schema', $data) && ! $skipFormSchemaNormalize) {
            $raw = $data['schema'];
            $model = $data['model'] ?? null;
            $formModelClass = is_string($model) && $model !== '' ? $model : null;
            $data['schema'] = app(FormSchemaNormalizer::class)->normalizedFormSchema(
                is_array($raw) || $raw === null ? $raw : null,
                $log,
                $formModelClass,
                null,
            );
        } elseif (in_array($view, ['list', 'dashboard'], true) && array_key_exists('schema', $data)) {
            $raw = $data['schema'];
            $data['schema'] = app(ListSchemaNormalizer::class)->normalizedListSchema(
                is_array($raw) || $raw === null ? $raw : null,
                $log,
            );
        }

        return self::applyCompositionDebug($data, $log);
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

        return $entity . '/' . $fileName;
    }

    private static function contextForDashboardComposition(): string
    {
        $slug = (string) config('flatpack.dashboard_entity', 'dashboard');
        $slug = trim($slug);

        return ($slug !== '' ? $slug : 'dashboard') . '/list.yaml';
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
