<?php

declare(strict_types=1);

namespace Flatpack\Http\Response;

use Flatpack\Schema\RelationFieldQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Paginated value/label pairs for relation pickers (combobox, embedded table columns, …).
 */
final class RelationOptionsPayload
{
    /**
     * @param  array<string, mixed>  $fieldDefinition
     *                                                 Must include {@code relation}, and label/value via {@code relation_name}/{@code relationValue} style keys.
     * @return array{
     *   data: list<array{value: string, label: string}>,
     *   meta: array{page: int, per_page: int, has_more: bool, next_page: int|null}
     * }
     */
    public static function forModelField(
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
            return self::emptyMeta($page, $perPage);
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
     * @return array{data: list<array{value: string, label: string}>, meta: array{page: int, per_page: int, has_more: false, next_page: null}}
     */
    public static function emptyMeta(int $page, int $perPage): array
    {
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
}
