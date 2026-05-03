<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Composition\FormComposition;
use Flatpack\Schema\Forms\NormalizedFormSchema;
use Flatpack\Schema\HeaderActions;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * Shared form page props builder for Flatpack form responses.
 */
trait BuildsFormPageProps
{
    /**
     * @param  array<string, array<string, mixed>>  $widgets
     * @param  array<string, mixed>|null  $widgetsSchema
     * @param  array<string, array<string, mixed>>  $sidebar_widgets
     * @param  array<string, mixed>|null  $sidebar_widgets_schema
     * @return array<string, mixed>
     */
    private function formPageProps(
        string $entity,
        FormComposition $form,
        ?NormalizedFormSchema $schema,
        string $mode,
        ?string $record,
        array $values,
        array $widgets = [],
        ?array $widgetsSchema = null,
        array $sidebar_widgets = [],
        ?array $sidebar_widgets_schema = null,
    ): array {
        $oldValues = old('values');
        if ($this->hasFlashedValidationErrors() && is_array($oldValues)) {
            $values = $oldValues;
        }

        $schemaArray = $schema?->toArray();

        return [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => $mode,
            'schema' => $schemaArray,
            'values' => $values,
            'form_actions' => HeaderActions::fromSchema($schemaArray),
            'widgets' => $widgets,
            'widgets_schema' => $widgetsSchema,
            'sidebar_widgets' => $sidebar_widgets,
            'sidebar_widgets_schema' => $sidebar_widgets_schema,
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
