<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Schema\Forms\FormFieldType;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Schema\RelationFieldQuery;
use Illuminate\Database\Eloquent\Model;
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

        return response()->json($this->relationOptionsPayload(
            modelClass: (string) ($form->model ?? ''),
            fieldDefinition: $fieldDefinition,
            request: $request,
        ));
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function relationFieldDefinition(?array $schema, string $fieldId): ?array
    {
        $fieldDefinition = FormSchemaFields::fieldDefinitionById($schema, $fieldId);
        if ($fieldDefinition === null || ! FormFieldType::isRelationField($fieldDefinition)) {
            return null;
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array{
     *   data: list<array{value: string, label: string}>,
     *   meta: array{page: int, per_page: int, has_more: bool, next_page: int|null}
     * }
     */
    private function relationOptionsPayload(
        string $modelClass,
        array $fieldDefinition,
        Request $request,
    ): array {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));
        $search = trim((string) $request->query('q', ''));
        $selected = trim((string) $request->query('selected', ''));

        $components = RelationFieldQuery::components($modelClass, $fieldDefinition);
        if ($components === null) {
            return [
                'data' => [],
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'has_more' => false,
                    'next_page' => null,
                ],
            ];
        }
        [$query, $labelField, $valueField] = $components;

        if ($search !== '') {
            $query->where($labelField, 'like', '%' . $search . '%');
        }

        $offset = ($page - 1) * $perPage;
        $records = $query
            ->orderBy($labelField)
            ->offset($offset)
            ->limit($perPage + 1)
            ->get([$valueField, $labelField]);
        $hasMore = $records->count() > $perPage;
        $pageItems = $records
            ->take($perPage)
            ->map(fn (Model $related): array => [
                'value' => (string) $related->getAttribute($valueField),
                'label' => (string) $related->getAttribute($labelField),
            ])
            ->filter(fn (array $option): bool => $option['value'] !== '' && $option['label'] !== '')
            ->values()
            ->all();

        if ($selected !== '' && ! collect($pageItems)->contains(fn (array $option): bool => $option['value'] === $selected)) {
            $selectedRecord = $query->getModel()::query()
                ->where($valueField, $selected)
                ->first([$valueField, $labelField]);
            if ($selectedRecord instanceof Model) {
                array_unshift($pageItems, [
                    'value' => (string) $selectedRecord->getAttribute($valueField),
                    'label' => (string) $selectedRecord->getAttribute($labelField),
                ]);
            }
        }

        return [
            'data' => $pageItems,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => $hasMore,
                'next_page' => $hasMore ? $page + 1 : null,
            ],
        ];
    }
}
