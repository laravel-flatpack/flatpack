<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Forms\Normalization\Pipes\LogUnknownFormRootKeysPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\MergeFormTabsIntoFieldsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\NormalizeFormFieldDefinitionsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\StripInvalidFormPresetsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\WarnUnknownFormActionsNestedKeysPipe;
use Flatpack\Schema\RelationFieldQuery;
use Flatpack\Schema\ResolvesLaravelPipeline;
use Flatpack\Services\Forms\FormRelationValuesHydrator;
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
    use ResolvesLaravelPipeline;

    public function __construct(
        private ?Pipeline $pipeline = null,
        private ?FormRelationValuesHydrator $relationValuesHydrator = null,
        private ?FormEmbeddedTableRelationTypeResolver $tableRelationTypeResolver = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    public function normalizedFormSchema(
        ?array $schema,
        ?CompositionDebugLog $debug = null,
        ?string $formModelClass = null,
        ?Model $formModel = null,
    ): ?array {
        if ($schema === null) {
            return null;
        }

        $state = new FormSchemaPipelineState($schema, $debug);

        /** @var FormSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                MergeFormTabsIntoFieldsPipe::class,
                LogUnknownFormRootKeysPipe::class,
                WarnUnknownFormActionsNestedKeysPipe::class,
                NormalizeFormFieldDefinitionsPipe::class,
                StripInvalidFormPresetsPipe::class,
            ])
            ->thenReturn();

        if ($formModelClass !== null) {
            $resolver = $this->tableRelationTypeResolver ?? new FormEmbeddedTableRelationTypeResolver;
            $enriched = $resolver->enrichFormSchema(
                $out->schema,
                $formModelClass,
                $formModel,
                $out->log,
            );
            if ($enriched !== null) {
                $out->schema = $enriched;
            }
        }

        return $out->schema;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function normalizeForFormPage(
        ?array $schema,
        string $debugContext,
        ?string $formModelClass = null,
        ?Model $formModel = null,
    ): FormSchemaNormalizationResult {
        $debugLog = config('app.debug') ? new CompositionDebugLog($debugContext) : null;
        $normalized = $this->normalizedFormSchema(
            schema: $schema,
            debug: $debugLog,
            formModelClass: $formModelClass,
            formModel: $formModel,
        );

        return new FormSchemaNormalizationResult(
            schema: $normalized,
            debugLog: $debugLog,
        );
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

            if (FormFieldType::isRelationBackedFileUpload($fieldDefinition)) {
                $values[$id] = $this->relationFileUploadValue($model, $fieldDefinition);
            } elseif (FormFieldType::shouldDeferToRelationSync($fieldDefinition)) {
                $values[$id] = $this->relationHydrator()->hydrate($model, $id, $fieldDefinition, $debug);
            } elseif (FormFieldType::isSingleRelationCombobox($fieldDefinition)) {
                $values[$id] = $this->singleRelationComboboxValue($model, $id, $fieldDefinition, $debug);
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
            if (! FormFieldType::isRelationBackedField($fieldDefinition)) {
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

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function singleRelationComboboxValue(
        Model $model,
        string $fieldId,
        array $fieldDefinition,
        ?CompositionDebugLog $debug,
    ): mixed {
        $direct = $model->getAttribute($fieldId);
        if (is_string($direct) || is_int($direct) || is_float($direct)) {
            return (string) $direct;
        }

        $relation = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        if ($relation === '' || ! method_exists($model, $relation)) {
            return null;
        }

        $valueKey = RelationFieldQuery::stringFromField(
            $fieldDefinition,
            'relation_value',
            'relationValue',
        );
        if ($valueKey === '') {
            $valueKey = 'id';
        }

        try {
            $model->loadMissing($relation);
        } catch (Throwable $e) {
            $debug?->add(sprintf(
                'Form field "%s": failed loading relation [%s] for combobox hydration: %s',
                $fieldId,
                $relation,
                $e->getMessage(),
            ));

            return null;
        }

        $related = $model->getRelation($relation);
        if (! $related instanceof Model) {
            return null;
        }

        $value = $valueKey === 'id' ? $related->getKey() : $related->getAttribute($valueKey);

        return $value !== null ? (string) $value : null;
    }

    private function relationHydrator(): FormRelationValuesHydrator
    {
        return $this->relationValuesHydrator ?? app(FormRelationValuesHydrator::class);
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array<string, mixed>>
     */
    private function relationFileUploadValue(Model $model, array $fieldDefinition): array
    {
        $relation = trim((string) ($fieldDefinition['relation'] ?? ''));
        if ($relation === '' || ! method_exists($model, $relation)) {
            return [];
        }

        $related = $model->getRelation($relation);
        if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
            return $related
                ->map(static fn (Model $item): array => [
                    'disk' => $item->getAttribute('disk'),
                    'path' => $item->getAttribute('path'),
                    'url' => $item->getAttribute('url'),
                    'name' => $item->getAttribute('name'),
                    'mime_type' => $item->getAttribute('mime_type'),
                    'size' => $item->getAttribute('size'),
                    'collection' => $item->getAttribute('collection'),
                ])
                ->values()
                ->all();
        }

        if ($related instanceof Model) {
            return [[
                'disk' => $related->getAttribute('disk'),
                'path' => $related->getAttribute('path'),
                'url' => $related->getAttribute('url'),
                'name' => $related->getAttribute('name'),
                'mime_type' => $related->getAttribute('mime_type'),
                'size' => $related->getAttribute('size'),
                'collection' => $related->getAttribute('collection'),
            ]];
        }

        return [];
    }
}
