<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\Controllers\Concerns\LoadsFormComposition;
use Flatpack\Http\Requests\EmbeddedTableColumnRelationOptionsRequest;
use Flatpack\Http\Response\FlatpackErrorPayload;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Forms\FormSchemaFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;

final readonly class EmbeddedTableColumnRelationOptionsController
{
    use LoadsFormComposition;

    public function __invoke(EmbeddedTableColumnRelationOptionsRequest $request, string $entity): JsonResponse
    {
        $form = $this->loadForm($entity);
        $rawSchema = $this->loadSchema($entity);
        $schema = CompositionTabsMerge::form($rawSchema) ?? $rawSchema;
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
}
