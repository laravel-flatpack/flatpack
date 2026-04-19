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
use Throwable;

/**
 * Prepares form YAML schema and field values for Inertia / JSON responses.
 * Normalization runs as a Laravel {@see Pipeline} of discrete validation / transform stages.
 */
final readonly class FormSchemaNormalizer
{
    public function __construct(
        private ?Pipeline $pipeline = null,
        private ?FormRelationValuesHydrator $relationValuesHydrator = null,
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
    public function formValuesFromModel(?Model $model, ?array $schema, ?CompositionDebugLog $debug = null): array
    {
        if (! $model instanceof Model || $schema === null) {
            return [];
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $this->eagerLoadRelationsForSchema($model, $fields, $debug);

        $values = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            if (FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                $values[$id] = $this->relationHydrator()->hydrate($model, $id, $fieldDefinition, $debug);
            } else {
                $values[$id] = $model->getAttribute($id);
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function eagerLoadRelationsForSchema(Model $model, array $fields, ?CompositionDebugLog $debug): void
    {
        $names = [];
        foreach ($fields as $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }
            if (! FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                continue;
            }
            $relation = isset($fieldDefinition['relation'])
                ? trim((string) $fieldDefinition['relation'])
                : '';
            if ($relation !== '') {
                $names[] = $relation;
                foreach (FormRelationValuesHydrator::nestedRelationNamesForEagerLoad($fieldDefinition) as $nested) {
                    $names[] = $relation . '.' . $nested;
                }
            }
        }

        $names = array_values(array_unique($names));
        if ($names === []) {
            return;
        }

        try {
            $model->load($names);
        } catch (Throwable $e) {
            $debug?->add(sprintf(
                'Form values: eager-loading relations [%s] failed: %s',
                implode(', ', $names),
                $e->getMessage(),
            ));
        }
    }

    private function relationHydrator(): FormRelationValuesHydrator
    {
        return $this->relationValuesHydrator ?? app(FormRelationValuesHydrator::class);
    }

    private function resolvePipeline(): Pipeline
    {
        return $this->pipeline ?? app(Pipeline::class);
    }
}
