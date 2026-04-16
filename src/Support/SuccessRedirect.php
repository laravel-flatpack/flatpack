<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Illuminate\Http\RedirectResponse;

/**
 * Allowed {@code success_redirect} string values from Flatpack YAML (forms, lists, bulk, row actions).
 */
final class SuccessRedirect
{
    public const ALLOWED = [
        'list',
        'edit',
        'create',
        'show',
        'back',
        'previous',
        'current',
        'stay',
    ];

    public static function normalize(mixed $raw): ?string
    {
        if ($raw === null || ! is_string($raw)) {
            return null;
        }

        $v = trim($raw);
        if ($v === '') {
            return null;
        }

        return in_array($v, self::ALLOWED, true) ? $v : null;
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
     * After a form save (create or edit). {@code $savedKey} is the persisted model key as string.
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
            'current', 'stay' => $wasCreate
                ? redirect()->route('flatpack.entities.create', [
                    'entity' => $entity,
                ])->setStatusCode(303)
                : redirect()->route('flatpack.entities.edit', [
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
