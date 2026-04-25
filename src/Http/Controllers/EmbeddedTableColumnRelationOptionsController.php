<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\Forms\FormSchemaFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class EmbeddedTableColumnRelationOptionsController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function __invoke(Request $request, string $entity): JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $tableFieldId = trim((string) $request->query('table_field', ''));
        $columnId = trim((string) $request->query('column_id', ''));
        if ($tableFieldId === '' || $columnId === '') {
            abort(404, 'Flatpack embedded table field or column is missing.');
        }

        $tableField = FormSchemaFields::fieldDefinitionById($schema, $tableFieldId);
        if ($tableField === null) {
            abort(404, 'Flatpack table field is not configured.');
        }
        if (trim((string) ($tableField['type'] ?? '')) !== 'table') {
            abort(404, 'Flatpack field is not a table.');
        }

        $column = FormSchemaFields::embeddedTableColumnById($tableField, $columnId);
        if ($column === null) {
            abort(404, 'Flatpack table column is not configured.');
        }
        if (trim((string) ($column['type'] ?? '')) !== 'relation') {
            abort(404, 'Flatpack table column is not a relation column.');
        }

        $parentClass = (string) ($form->model ?? '');
        if ($parentClass === '' || ! class_exists($parentClass) || ! is_subclass_of($parentClass, Model::class)) {
            abort(404, 'Flatpack form model is not configured.');
        }

        $tableRelation = trim((string) ($tableField['relation'] ?? ''));
        if ($tableRelation === '' || ! method_exists($parentClass, $tableRelation)) {
            abort(404, 'Flatpack table relation is not available on the form model.');
        }

        $parent = new $parentClass;
        $rel = $parent->{$tableRelation}();
        if (! $rel instanceof Relation) {
            abort(404, 'Flatpack table relation is not an Eloquent relation.');
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
