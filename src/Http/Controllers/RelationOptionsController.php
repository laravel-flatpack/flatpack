<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\Controllers\Concerns\LoadsFormComposition;
use Flatpack\Http\Requests\RelationOptionsRequest;
use Flatpack\Http\Response\FlatpackErrorPayload;
use Flatpack\Http\Response\RelationOptionsPayload;
use Flatpack\Schema\CompositionTabsMerge;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Forms\FormSchemaFields;
use Illuminate\Http\JsonResponse;

final readonly class RelationOptionsController
{
    use LoadsFormComposition;

    public function __invoke(RelationOptionsRequest $request, string $entity): JsonResponse
    {
        $form = $this->loadForm($entity);
        $rawSchema = $this->loadSchema($entity);
        $schema = CompositionTabsMerge::form($rawSchema) ?? $rawSchema;
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
