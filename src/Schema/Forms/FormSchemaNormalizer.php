<?php

declare(strict_types=1);

namespace Flatpack\Schema\Forms;

use Flatpack\Schema\Forms\Normalization\FileUploadRelationHydrator;
use Flatpack\Schema\Forms\Normalization\FormSchemaPipelineState;
use Flatpack\Schema\Forms\Normalization\Pipes\MergeFormSidebarFieldsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\MergeFormTabsIntoFieldsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\NormalizeFormFieldDefinitionsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\StripInvalidFormPresetsPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\StripUnknownFormRootKeysPipe;
use Flatpack\Schema\Forms\Normalization\Pipes\WarnUnknownFormActionsNestedKeysPipe;
use Flatpack\Schema\RelationFieldQuery;
use Flatpack\Schema\ResolvesLaravelPipeline;
use Flatpack\Services\Forms\FormRelationValuesHydrator;
use Flatpack\Services\Uploads\FileUploadBrowserUrl;
use Flatpack\Support\CompositionDebugContext;
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
        private ?CompositionDebugContext $compositionDebugContext = null,
        private ?FileUploadBrowserUrl $fileUploadBrowserUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function normalizedFormSchema(
        ?array $schema,
        ?CompositionDebugLog $debug = null,
        ?string $formModelClass = null,
        ?Model $formModel = null,
    ): ?NormalizedFormSchema {
        if ($schema === null) {
            return null;
        }

        $state = new FormSchemaPipelineState($schema, CompositionDebugContext::resolveOptional($debug));

        /** @var FormSchemaPipelineState $out */
        $out = $this->resolvePipeline()
            ->send($state)
            ->through([
                MergeFormTabsIntoFieldsPipe::class,
                MergeFormSidebarFieldsPipe::class,
                StripUnknownFormRootKeysPipe::class,
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

        $sidebarWidgetsRaw = $out->schema['_sidebar_widgets_pending'] ?? null;
        unset($out->schema['_sidebar_widgets_pending']);

        return new NormalizedFormSchema(
            $out->schema,
            is_array($sidebarWidgetsRaw) && $sidebarWidgetsRaw !== [] ? $sidebarWidgetsRaw : null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function normalizeForFormPage(
        ?array $schema,
        string $debugContext,
        ?string $formModelClass = null,
        ?Model $formModel = null,
    ): ?NormalizedFormSchema {
        $this->compositionDebug()->activate($debugContext);
        if ($schema === null) {
            return null;
        }

        return $this->normalizedFormSchema(
            schema: $schema,
            debug: null,
            formModelClass: $formModelClass,
            formModel: $formModel,
        );
    }

    public function formValuesFromModel(?Model $model, ?NormalizedFormSchema $schema, ?CompositionDebugLog $debug = null): array
    {
        if (! $model instanceof Model || $schema === null) {
            return [];
        }

        $debug = CompositionDebugContext::resolveOptional($debug);

        $schemaArray = $schema->toArray();
        $fields = $schemaArray['fields'] ?? null;
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
            } elseif (FormFieldType::isUrlOrImageModeFileUpload($fieldDefinition)) {
                $values[$id] = $this->urlFileUploadValue($model, $id, $fieldDefinition);
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

    private function compositionDebug(): CompositionDebugContext
    {
        return $this->compositionDebugContext ?? app(CompositionDebugContext::class);
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

    private function fileUploadBrowserUrl(): FileUploadBrowserUrl
    {
        return $this->fileUploadBrowserUrl ?? app(FileUploadBrowserUrl::class);
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
                ->map(fn (Model $item): array => $this->fileUploadBrowserUrl()->ensureFragmentBrowseUrl(
                    FileUploadRelationHydrator::fragmentFromRelatedModel($item),
                    $fieldDefinition,
                ))
                ->values()
                ->all();
        }

        if ($related instanceof Model) {
            return [
                $this->fileUploadBrowserUrl()->ensureFragmentBrowseUrl(
                    FileUploadRelationHydrator::fragmentFromRelatedModel($related),
                    $fieldDefinition,
                ),
            ];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function urlFileUploadValue(Model $model, string $fieldId, array $fieldDefinition): ?array
    {
        $targetColumn = trim((string) ($fieldDefinition['target_column'] ?? ''));
        $sourceColumn = $targetColumn !== '' ? $targetColumn : $fieldId;
        $stored = $model->getAttribute($sourceColumn);

        $multiple = ($fieldDefinition['multiple'] ?? false) === true;
        $persistAs = trim((string) ($fieldDefinition['persist_as'] ?? 'string'));
        $urls = $this->storedUrlFileUploadUrls($stored, $multiple, $persistAs);
        $browser = $this->fileUploadBrowserUrl();
        $fragments = array_map(
            fn (string $segment): array => [
                'url' => $browser->resolveBrowseUrlForField($fieldDefinition, $segment),
            ],
            $urls,
        );

        if ($multiple) {
            return $fragments;
        }

        return $fragments[0] ?? null;
    }

    /**
     * @return list<string>
     */
    private function storedUrlFileUploadUrls(mixed $stored, bool $multiple, string $persistAs): array
    {
        if (is_string($stored)) {
            if (! $multiple) {
                $single = trim($stored);

                return $single === '' ? [] : [$single];
            }

            if ($persistAs !== 'string') {
                $single = trim($stored);

                return $single === '' ? [] : [$single];
            }

            return array_values(array_filter(array_map(
                trim(...),
                explode(',', $stored),
            ), static fn (string $item): bool => $item !== ''));
        }

        if (! is_array($stored)) {
            return [];
        }

        $urls = [];
        foreach ($stored as $item) {
            if (is_string($item)) {
                $value = trim($item);
                if ($value !== '') {
                    $urls[] = $value;
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $value = trim((string) ($item['url'] ?? $item['path'] ?? ''));
            if ($value !== '') {
                $urls[] = $value;
            }
        }

        return $urls;
    }
}
