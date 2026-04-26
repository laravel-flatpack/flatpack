<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Schema\Forms\FormSchemaNormalizationResult;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
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
        if ($schema === null || ! array_key_exists('fields', $schema)) {
            return false;
        }
        $fields = $schema['fields'];
        if (! is_array($fields)) {
            return false;
        }

        return count($fields) > 0;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function normalizeSchemaForFormPage(
        string $entity,
        ?array $schema,
        string $modelClass,
        ?Model $model = null,
    ): FormSchemaNormalizationResult {
        return $this->formSchemaNormalizer()->normalizeForFormPage(
            $schema,
            $entity . '/form.yaml',
            $modelClass !== '' ? $modelClass : null,
            $model,
        );
    }

    private function formSchemaNormalizer(): FormSchemaNormalizer
    {
        return app(FormSchemaNormalizer::class);
    }
}
