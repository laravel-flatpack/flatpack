<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        if ($schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return null;
        }

        foreach ($fields as $key => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $key));
            if ($id !== $fieldId) {
                continue;
            }

            $type = isset($fieldDefinition['type'])
                ? trim((string) $fieldDefinition['type'])
                : '';
            if ($type !== 'relation' && ! ($type === 'combobox' && isset($fieldDefinition['relation']))) {
                return null;
            }

            return $fieldDefinition;
        }

        return null;
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

        $components = $this->relationQueryComponents($modelClass, $fieldDefinition);
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

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array{Builder<Model>, string, string}|null
     */
    private function relationQueryComponents(
        string $modelClass,
        array $fieldDefinition,
    ): ?array {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $relationName = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        $labelField = $this->stringFromField($fieldDefinition, 'relation_name', 'relationName');
        $valueField = $this->stringFromField($fieldDefinition, 'relation_value', 'relationValue');

        if ($relationName === '' || $labelField === '' || $valueField === '') {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        if (! method_exists($model, $relationName)) {
            return null;
        }

        $relation = $model->{$relationName}();
        if (! $relation instanceof Relation) {
            return null;
        }

        return [$relation->getRelated()->newQuery(), $labelField, $valueField];
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     */
    private function stringFromField(
        array $fieldDefinition,
        string $snakeKey,
        string $camelKey,
    ): string {
        foreach ([$snakeKey, $camelKey] as $key) {
            if (isset($fieldDefinition[$key]) && is_string($fieldDefinition[$key])) {
                return trim($fieldDefinition[$key]);
            }
        }

        return '';
    }
}

