<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\FormComposition;
use Flatpack\Schema\HeaderActions;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

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
        ?CompositionDebugLog $debugLog = null,
    ): array {
        $oldValues = old('values');
        if ($this->hasFlashedValidationErrors() && is_array($oldValues)) {
            $values = $oldValues;
        }

        return [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => $mode,
            'schema' => $schema,
            'values' => $values,
            'form_actions' => HeaderActions::fromSchema($schema, $debugLog),
        ];
    }

    private function hasFlashedValidationErrors(): bool
    {
        $errors = session('errors');
        if ($errors instanceof ViewErrorBag) {
            return $errors->isNotEmpty();
        }
        if ($errors instanceof MessageBag) {
            return $errors->isNotEmpty();
        }
        if (is_array($errors)) {
            return $errors !== [];
        }

        return false;
    }
}
