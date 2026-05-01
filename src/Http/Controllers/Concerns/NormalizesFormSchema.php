<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Http\FlatpackResponse;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\Forms\NormalizedFormSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared form schema normalization for controller form-page flows.
 */
trait NormalizesFormSchema
{
    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function hasRenderableFields(?array $schema): bool
    {
        if ($schema === null) {
            return false;
        }

        $fields = $schema['fields'] ?? null;
        if (is_array($fields) && $fields !== []) {
            return true;
        }

        $tabs = $schema['tabs'] ?? null;
        if (! is_array($tabs) || $tabs === []) {
            return false;
        }

        foreach ($tabs as $panel) {
            if (! is_array($panel)) {
                continue;
            }

            $tabFields = $panel['fields'] ?? null;
            if (is_array($tabFields) && $tabFields !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function normalizeSchemaForFormPage(
        string $entity,
        ?array $schema,
        string $modelClass,
        ?Model $model = null,
    ): ?NormalizedFormSchema {
        return $this->formSchemaNormalizer()->normalizeForFormPage(
            $schema,
            FlatpackResponse::compositionDebugContextForEntity($entity, 'form.yaml'),
            $modelClass !== '' ? $modelClass : null,
            $model,
        );
    }

    private function formSchemaNormalizer(): FormSchemaNormalizer
    {
        return app(FormSchemaNormalizer::class);
    }
}
