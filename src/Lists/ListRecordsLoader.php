<?php

declare(strict_types=1);

namespace Flatpack\Lists;

use Illuminate\Database\Eloquent\Model;

/**
 * Loads tabular rows for a Flatpack entity list from the configured Eloquent model.
 */
final readonly class ListRecordsLoader
{
    private const int MAX_ROWS = 100;

    /**
     * @return list<array<string, mixed>>
     */
    public function load(?string $modelClass, ?array $schema): array
    {
        if ($modelClass === null || $modelClass === '' || ! class_exists($modelClass)) {
            return [];
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $columnKeys = $this->columnKeysFromListSchema($schema);
        if ($columnKeys === []) {
            return [];
        }

        $model = new $modelClass();
        $keyName = $model->getKeyName();

        if (! in_array($keyName, $columnKeys, true)) {
            $columnKeys = array_merge([$keyName], $columnKeys);
        }

        $rows = [];
        foreach (
            $model
                ->newQuery()
                ->select($columnKeys)
                ->orderByDesc($model->getQualifiedKeyName())
                ->limit(self::MAX_ROWS)
                ->get() as $row
        ) {
            $rows[] = $row->only($columnKeys);
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function columnKeysFromListSchema(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if ($columns === null || ! is_array($columns)) {
            return [];
        }

        if ($columns === []) {
            return [];
        }

        if (array_is_list($columns)) {
            $keys = [];
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    continue;
                }
                if (! isset($column['id'])) {
                    continue;
                }
                $id = (string) $column['id'];
                if ($id !== '') {
                    $keys[] = $id;
                }
            }

            return $keys;
        }

        return array_keys($columns);
    }
}
