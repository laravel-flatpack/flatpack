<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Illuminate\Http\RedirectResponse;

/**
 * Allowed {@code success_redirect} string values from Flatpack YAML (forms, lists, bulk, row actions).
 * YAML may use boolean {@code true} as an alias for {@code list}.
 *
 * Intentionally static while the surface stays small (YAML normalization + a few call sites). If host
 * apps need pluggable redirect resolution, introduce an injectable service and delegate these methods.
 *
 * @see CompositionSchemaKeys::SUCCESS_REDIRECT_VALUES
 */
final class SuccessRedirect
{
    public static function normalize(mixed $raw): ?string
    {
        if ($raw === true) {
            return 'list';
        }

        if ($raw === false || $raw === null) {
            return null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $v = trim($raw);
        if ($v === '') {
            return null;
        }

        if (strcasecmp($v, 'true') === 0) {
            return 'list';
        }

        return in_array($v, CompositionSchemaKeys::SUCCESS_REDIRECT_VALUES, true) ? $v : null;
    }

    /**
     * Reads {@code actions.save.success_redirect} from a raw form.yaml schema array.
     */
    public static function fromFormSchema(?array $schema): ?string
    {
        if ($schema === null) {
            return null;
        }

        $actions = $schema['actions'] ?? null;
        if (! is_array($actions)) {
            return null;
        }

        $save = $actions['save'] ?? null;
        if (! is_array($save)) {
            return null;
        }

        return self::normalize($save['success_redirect'] ?? null);
    }

    /**
     * Resolves redirect after form submit when YAML defines per-action {@code success_redirect}.
     * Prefers {@code form_action_id} (YAML key), then {@code actions.save} when that key exists,
     * otherwise the first action block whose {@code action} matches the submitted handler name.
     *
     * @param  non-empty-string|null  $formActionId  YAML action key from {@see HeaderActions} ({@code id} field).
     */
    public static function successRedirectForFormSubmit(
        ?array $schema,
        ?string $formActionId,
        string $submittedAction,
    ): ?string {
        $id = $formActionId !== null ? trim($formActionId) : '';
        if ($schema === null) {
            return null;
        }

        $actions = $schema['actions'] ?? null;
        if (! is_array($actions)) {
            return null;
        }

        if ($id !== '') {
            $block = $actions[$id] ?? null;
            if (is_array($block)) {
                $direct = self::normalize($block['success_redirect'] ?? null);
                if ($direct !== null) {
                    return $direct;
                }
            }
        }

        $saveBlock = $actions['save'] ?? null;
        if (is_array($saveBlock)) {
            return self::normalize($saveBlock['success_redirect'] ?? null);
        }

        $needle = trim($submittedAction);
        if ($needle === '') {
            return null;
        }

        foreach ($actions as $block) {
            if (! is_array($block)) {
                continue;
            }
            $blockAction = isset($block['action']) ? trim((string) $block['action']) : '';
            if ($blockAction !== $needle) {
                continue;
            }
            $direct = self::normalize($block['success_redirect'] ?? null);
            if ($direct !== null) {
                return $direct;
            }
        }

        return null;
    }

    /**
     * After a form save (create or edit). {@code $savedKey} is the persisted model key as string.
     * "current"/"stay" keep the user on the saved record edit page.
     */
    public static function responseForFormSave(
        string $target,
        string $entity,
        bool $wasCreate,
        string $savedKey,
    ): RedirectResponse {
        return match ($target) {
            'list' => redirect()->route('flatpack.entities.index', [
                'entity' => $entity,
            ])->setStatusCode(303),
            'create' => redirect()->route('flatpack.entities.create', [
                'entity' => $entity,
            ])->setStatusCode(303),
            'edit', 'show' => redirect()->route('flatpack.entities.edit', [
                'entity' => $entity,
                'record' => $savedKey,
            ])->setStatusCode(303),
            'back', 'previous' => back(303),
            'current', 'stay' => redirect()->route('flatpack.entities.edit', [
                'entity' => $entity,
                'record' => $savedKey,
            ])->setStatusCode(303),
            default => redirect()->route('flatpack.entities.edit', [
                'entity' => $entity,
                'record' => $savedKey,
            ])->setStatusCode(303),
        };
    }

    /**
     * List header action, bulk action, or row action (non-form-save).
     *
     * @param  non-empty-string|null  $record
     */
    public static function responseForEntityAction(
        string $target,
        string $entity,
        ?string $record,
    ): RedirectResponse {
        return match ($target) {
            'list' => redirect()->route('flatpack.entities.index', [
                'entity' => $entity,
            ])->setStatusCode(303),
            'create' => redirect()->route('flatpack.entities.create', [
                'entity' => $entity,
            ])->setStatusCode(303),
            'edit', 'show' => ($record !== null && $record !== '')
                ? redirect()->route('flatpack.entities.edit', [
                    'entity' => $entity,
                    'record' => $record,
                ])->setStatusCode(303)
                : redirect()->route('flatpack.entities.index', [
                    'entity' => $entity,
                ])->setStatusCode(303),
            'back', 'previous' => back(303),
            'current', 'stay' => ($record !== null && $record !== '')
                ? redirect()->route('flatpack.entities.edit', [
                    'entity' => $entity,
                    'record' => $record,
                ])->setStatusCode(303)
                : back(303),
            default => back(303),
        };
    }
}
