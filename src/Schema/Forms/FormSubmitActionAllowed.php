<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Schema\HeaderActions;

/**
 * Validates {@code action} request values against the entity form schema toolbar.
 */
final class FormSubmitActionAllowed
{
    /**
     * Distinct handler names declared on non-{@code href} form actions: top-level {@code actions}
     * plus every {@code type: toolbar} field’s {@code actions} map after the same merge as the
     * form page ({@see FormCompositionMergeForPersistence}): tabs and sidebar are flattened into
     * {@code fields.*}, so toolbar rows live under {@code fields.*.actions} whether declared at the
     * root, under {@code tabs.*.fields}, or under {@code sidebar}. When the form defines no such
     * actions, only {@code save} is allowed (implicit default toolbar).
     *
     * @return list<string>
     */
    public static function allowedActionStrings(?array $schema): array
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
        foreach (self::actionStringsFromToolbarFields($schema) as $action) {
            $out[] = $action;
        }
        $out = array_values(array_unique($out, SORT_STRING));
        if ($out === []) {
            return ['save'];
        }

        return $out;
    }

    /**
     * Collects {@code action} handler names from normalized {@code type: toolbar} fields.
     *
     * @return list<string>
     */
    private static function actionStringsFromToolbarFields(?array $schema): array
    {
        if ($schema === null) {
            return [];
        }
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }
        $out = [];
        foreach ($fields as $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }
            $type = isset($fieldDefinition['type'])
                ? trim((string) $fieldDefinition['type'])
                : '';
            if ($type !== 'toolbar') {
                continue;
            }
            $rows = $fieldDefinition['actions'] ?? null;
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (isset($row['href'])) {
                    continue;
                }
                $action = isset($row['action']) ? trim((string) $row['action']) : '';
                if ($action !== '') {
                    $out[] = $action;
                }
            }
        }

        return $out;
    }
}
