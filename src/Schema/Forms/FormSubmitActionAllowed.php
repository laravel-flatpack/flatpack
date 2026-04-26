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
     * Distinct handler names declared on non-{@code href} form actions. When the form defines no such actions, only {@code save} is allowed (implicit default toolbar).
     *
     * @return list<string>
     */
    public static function allowedActionStrings(?array $schema): array
    {
        $actions = HeaderActions::fromSchema($schema);
        $out = [];
        foreach ($actions as $row) {
            if (isset($row['href'])) {
                continue;
            }
            $action = isset($row['action']) ? trim((string) $row['action']) : '';
            if ($action !== '') {
                $out[] = $action;
            }
        }
        $out = array_values(array_unique($out, SORT_STRING));
        if ($out === []) {
            return ['save'];
        }

        return $out;
    }
}
