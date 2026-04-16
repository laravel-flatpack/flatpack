<?php

declare(strict_types=1);

namespace Flatpack\Support;

/**
 * Locates {@code success_redirect} on list/form YAML for entity actions.
 */
final class SuccessRedirectSchema
{
    public static function findForListHeaderAction(
        ?array $listSchema,
        string $actionName,
    ): ?string {
        return self::findInHeaderActions($listSchema, $actionName);
    }

    public static function findForBulkAction(
        ?array $listSchema,
        string $actionName,
    ): ?string {
        if ($listSchema === null) {
            return null;
        }

        $bulk = $listSchema['bulk_actions'] ?? null;
        if (! is_array($bulk)) {
            return null;
        }

        foreach ($bulk as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $a = trim((string) ($definition['action'] ?? ''));
            if ($a === $actionName) {
                return SuccessRedirect::normalize($definition['success_redirect'] ?? null);
            }
        }

        return null;
    }

    /**
     * Row actions: form.yaml actions, list.yaml actions, then list column action buttons.
     *
     * @return non-empty-string|null
     */
    public static function findForRowAction(
        ?array $formSchema,
        ?array $listSchema,
        string $actionName,
    ): ?string {
        foreach ([$formSchema, $listSchema] as $schema) {
            $fromHeader = self::findInHeaderActions($schema, $actionName);
            if ($fromHeader !== null) {
                return $fromHeader;
            }
        }

        return self::findInColumnActions($listSchema, $actionName);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private static function findInHeaderActions(?array $schema, string $actionName): ?string
    {
        if ($schema === null) {
            return null;
        }

        $actions = $schema['actions'] ?? null;
        if (! is_array($actions)) {
            return null;
        }

        foreach ($actions as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $a = trim((string) ($definition['action'] ?? ''));
            if ($a === $actionName) {
                return SuccessRedirect::normalize($definition['success_redirect'] ?? null);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private static function findInColumnActions(?array $schema, string $actionName): ?string
    {
        if ($schema === null) {
            return null;
        }

        $columns = $schema['columns'] ?? null;
        if (! is_array($columns)) {
            return null;
        }

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $buttons = $column['actions'] ?? null;
            if (! is_array($buttons)) {
                continue;
            }

            foreach ($buttons as $button) {
                if (! is_array($button)) {
                    continue;
                }

                $a = trim((string) ($button['action'] ?? ''));
                if ($a === $actionName) {
                    return SuccessRedirect::normalize($button['success_redirect'] ?? null);
                }
            }
        }

        return null;
    }
}
