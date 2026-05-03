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

        if ($this->sidebarSpecifiesRenderableContent($schema)) {
            return true;
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
     * Pre-expansion check: external sidebar paths count as content; inline sidebar may list fields or widgets.
     *
     * @param  array<string, mixed>  $schema
     */
    private function sidebarSpecifiesRenderableContent(array $schema): bool
    {
        $sidebar = $schema['sidebar'] ?? null;
        if (is_string($sidebar)) {
            return trim($sidebar) !== '';
        }

        if (! is_array($sidebar) || $sidebar === []) {
            return false;
        }

        $explicit =
            array_key_exists('fields', $sidebar) || array_key_exists('widgets', $sidebar);

        if ($explicit) {
            $sidebarFields = $sidebar['fields'] ?? null;
            $sidebarWidgets = $sidebar['widgets'] ?? null;

            return (is_array($sidebarFields) && $sidebarFields !== [])
                || (is_array($sidebarWidgets) && $sidebarWidgets !== []);
        }

        return true;
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
