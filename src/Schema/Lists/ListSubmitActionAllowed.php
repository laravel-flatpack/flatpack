<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Schema\HeaderActions;
use Flatpack\Services\Lists\ActiveTabResolver;

/**
 * Validates {@code action} request values against entity list / row / bulk YAML declarations.
 */
final class ListSubmitActionAllowed
{
    /**
     * Distinct handler names on non-{@code href} list header actions ({@see HeaderActions::fromSchema}).
     *
     * @return list<string>
     */
    public static function allowedListHeaderActionStrings(?array $schema): array
    {
        $out = [];
        foreach (HeaderActions::fromSchema($schema) as $row) {
            if (isset($row['href'])) {
                continue;
            }
            $action = isset($row['action']) ? trim((string) $row['action']) : '';
            if ($action !== '') {
                $out[] = $action;
            }
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    /**
     * Row actions: form header actions, list header actions, then {@code columns.*.actions} buttons.
     *
     * @return list<string>
     */
    public static function allowedRowActionStrings(?array $formSchema, ?array $listSchema): array
    {
        $out = [];
        foreach (self::allowedListHeaderActionStrings($formSchema) as $action) {
            $out[] = $action;
        }
        foreach (self::allowedListHeaderActionStrings($listSchema) as $action) {
            $out[] = $action;
        }
        foreach (self::actionStringsFromColumnActions($listSchema) as $action) {
            $out[] = $action;
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    /**
     * Bulk actions from the effective list schema for the requested tab ({@see ActiveTabResolver}).
     *
     * @return list<string>
     */
    public static function allowedBulkActionStrings(
        ?array $schema,
        ?string $activeTabId = null,
        ?ActiveTabResolver $activeTabResolver = null,
    ): array {
        $resolver = $activeTabResolver ?? app(ActiveTabResolver::class);
        $tabId = $activeTabId ?? '';
        $resolved = $resolver->resolveWithSchema($schema, $tabId);
        $effectiveSchema = $resolved->effectiveSchema?->toArray() ?? $schema;

        $out = [];
        foreach (BulkActions::fromSchema($effectiveSchema) as $row) {
            $out[] = $row['action'];
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    /**
     * Dashboard table/grid widget row actions: optional widget {@code actions} plus column action buttons.
     *
     * @return list<string>
     */
    public static function allowedWidgetRowActionStrings(?array $widgetDefinition): array
    {
        if ($widgetDefinition === null) {
            return [];
        }

        $out = self::allowedListHeaderActionStrings($widgetDefinition);
        foreach (self::actionStringsFromColumnActions($widgetDefinition) as $action) {
            $out[] = $action;
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    /**
     * Dashboard table/grid widget bulk actions (normalized list or raw keyed map).
     *
     * @return list<string>
     */
    public static function allowedWidgetBulkActionStrings(?array $widgetDefinition): array
    {
        if ($widgetDefinition === null) {
            return [];
        }

        $raw = $widgetDefinition['bulk_actions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        if (array_is_list($raw)) {
            $out = [];
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $action = trim((string) ($row['action'] ?? ''));
                if ($action !== '') {
                    $out[] = $action;
                }
            }

            return array_values(array_unique($out, SORT_STRING));
        }

        return self::allowedBulkActionStrings(['bulk_actions' => $raw]);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<string>
     */
    private static function actionStringsFromColumnActions(?array $schema): array
    {
        if ($schema === null) {
            return [];
        }

        $columns = $schema['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        $out = [];
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

                $action = trim((string) ($button['action'] ?? ''));
                if ($action !== '') {
                    $out[] = $action;
                }
            }
        }

        return $out;
    }
}
