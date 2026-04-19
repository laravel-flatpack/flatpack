<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Forms\Normalization\Pipes\LogUnknownFormRootKeysPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\NormalizeFormFieldDefinitionsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\StripInvalidFormPresetsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\WarnUnknownFormActionsNestedKeysPipe;
use Flatpack\Support\CompositionDebugLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;

/**
 * Prepares form YAML schema and field values for Inertia / JSON responses.
 * Normalization runs as a Laravel {@see Pipeline} of discrete validation / transform stages.
 */
final readonly class FormSchemaNormalizer
{
    public function __construct(
        private ?Pipeline $pipeline = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalizedFormSchema(?array $schema, ?CompositionDebugLog $debug = null): ?array
    {
        if ($schema === null) {
            return null;
        }

        $state = new FormSchemaPipelineState($schema, $debug);

        /** @var FormSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                LogUnknownFormRootKeysPipe::class,
                WarnUnknownFormActionsNestedKeysPipe::class,
                NormalizeFormFieldDefinitionsPipe::class,
                StripInvalidFormPresetsPipe::class,
            ])
            ->thenReturn();

        return $out->schema;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function formValuesFromModel(?Model $model, ?array $schema): array
    {
        if (! $model instanceof Model || $schema === null) {
            return [];
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $values = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $values[$id] = $model->getAttribute($id);
        }

        return $values;
    }

    private function resolvePipeline(): Pipeline
    {
        return $this->pipeline ?? app(Pipeline::class);
    }
}
