<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\FormComposition;
use Flatpack\Schema\HeaderActions;

/**
 * Shared form page props builder for Flatpack form responses.
 */
trait BuildsFormPageProps
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function formPageProps(
        string $entity,
        FormComposition $form,
        ?array $schema,
        string $mode,
        ?string $record,
        array $values,
    ): array {
        return [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => $mode,
            'schema' => $schema,
            'values' => $values,
            'form_actions' => HeaderActions::fromSchema($schema),
        ];
    }
}
