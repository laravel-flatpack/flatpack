<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Forms\FormSchemaFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class RelationOptionsController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function __invoke(Request $request, string $entity): JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $fieldId = trim((string) $request->query('field', ''));
        if ($fieldId === '') {
            abort(404, 'Flatpack relation field is missing.');
        }

        $fieldDefinition = $this->relationFieldDefinition($schema, $fieldId);
        if ($fieldDefinition === null) {
            abort(404, 'Flatpack relation field is not configured.');
        }

        return response()->json(RelationOptionsPayload::forModelField(
            (string) ($form->model ?? ''),
            $fieldDefinition,
            $request,
        ));
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
