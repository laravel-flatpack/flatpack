<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\FormComposition;
use Flatpack\Http\Controllers\Concerns\LoadsFormComposition;
use Flatpack\Http\Requests\EmbeddedTableColumnRelationOptionsRequest;
use Flatpack\Http\Requests\RelationOptionsRequest;
use Flatpack\Http\Response\FlatpackErrorPayload;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;

final readonly class RelationOptionsController
{
    use LoadsFormComposition;

    public function __construct(
        private FormSchemaNormalizer $formSchemaNormalizer,
    ) {}

    public function field(RelationOptionsRequest $request, string $entity): JsonResponse
    {
        $form = $this->loadForm($entity);
        $rawSchema = $this->loadSchema($entity);
        $schema = $this->normalizedFormSchemaArray($form, $rawSchema);
        $fieldId = trim((string) $request->validated('field'));

        $fieldDefinition = $this->relationFieldDefinition($schema, $fieldId);
        if ($fieldDefinition === null) {
            return FlatpackErrorPayload::notFound('Flatpack relation field is not configured.');
        }

        return response()->json(RelationOptionsPayload::forModelField(
            $this->formModelClass($form),
            $fieldDefinition,
            $request,
        ));
    }

    public function embeddedTableColumn(EmbeddedTableColumnRelationOptionsRequest $request, string $entity): JsonResponse
    {
        $form = $this->loadForm($entity);
        $rawSchema = $this->loadSchema($entity);
        $schema = $this->normalizedFormSchemaArray($form, $rawSchema);
        $tableFieldId = trim((string) $request->validated('table_field'));
        $columnId = trim((string) $request->validated('column_id'));

        $tableField = FormSchemaFields::fieldDefinitionById($schema, $tableFieldId);
        if ($tableField === null) {
            return FlatpackErrorPayload::notFound('Flatpack table field is not configured.');
        }
        if (trim((string) ($tableField['type'] ?? '')) !== 'table') {
            return FlatpackErrorPayload::notFound('Flatpack field is not a table.');
        }

        $column = FormSchemaFields::embeddedTableColumnById($tableField, $columnId);
        if ($column === null) {
            return FlatpackErrorPayload::notFound('Flatpack table column is not configured.');
        }
        if (trim((string) ($column['type'] ?? '')) !== 'relation') {
            return FlatpackErrorPayload::notFound('Flatpack table column is not a relation column.');
        }

        $parentClass = $this->formModelClass($form);
        if ($parentClass === '' || ! class_exists($parentClass) || ! is_subclass_of($parentClass, Model::class)) {
            return FlatpackErrorPayload::notFound('Flatpack form model is not configured.');
        }

        $tableRelation = trim((string) ($tableField['relation'] ?? ''));
        if ($tableRelation === '' || ! method_exists($parentClass, $tableRelation)) {
            return FlatpackErrorPayload::notFound('Flatpack table relation is not available on the form model.');
        }

        $parent = new $parentClass;
        $rel = $parent->{$tableRelation}();
        if (! $rel instanceof Relation) {
            return FlatpackErrorPayload::notFound('Flatpack table relation is not an Eloquent relation.');
        }

        $related = $rel->getRelated();
        $childClass = $related::class;

        $fieldDef = $column;
        $fieldDef['type'] = 'combobox';

        return response()->json(RelationOptionsPayload::forModelField(
            $childClass,
            $fieldDef,
            $request,
        ));
    }

    /**
     * Same pipeline as the form page (tabs + sidebar merged into {@code fields}, field defs normalized).
     *
     * @param  array<string, mixed>|null  $rawSchema
     * @return array<string, mixed>|null
     */
    private function normalizedFormSchemaArray(FormComposition $form, ?array $rawSchema): ?array
    {
        if ($rawSchema === null) {
            return null;
        }

        $modelClass = $this->formModelClass($form);
        $normalized = $this->formSchemaNormalizer->normalizedFormSchema(
            $rawSchema,
            null,
            $modelClass !== '' ? $modelClass : null,
            null,
        );

        return $normalized?->toArray();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function relationFieldDefinition(?array $schema, string $fieldId): ?array
    {
        $fieldDefinition = FormSchemaFields::fieldDefinitionById($schema, $fieldId);
        if ($fieldDefinition === null || ! FormFieldType::isRelationBackedCombobox($fieldDefinition)) {
            return null;
        }

        return $fieldDefinition;
    }
}
