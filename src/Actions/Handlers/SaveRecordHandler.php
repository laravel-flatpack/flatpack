<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Illuminate\Database\Eloquent\Model;

final class SaveRecordHandler implements FlatpackAction
{
    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $context->model;
        if (! $model instanceof Model) {
            return null;
        }

        $values = $context->request->input('values');
        if (! is_array($values)) {
            $field = trim((string) $context->request->input('field', ''));
            if ($field === '') {
                return $model;
            }
            $values = [$field => $context->request->input('value')];
        }

        $editableColumns = $this->editableColumnsFromSchema($context->schema);
        $filtered = [];

        foreach ($values as $field => $value) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            if (! isset($editableColumns[$field])) {
                continue;
            }
            $filtered[$field] = $value;
        }

        if ($filtered === []) {
            return $model;
        }

        $model->fill($filtered);
        $model->save();

        return $model->fresh();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, true>
     */
    private function editableColumnsFromSchema(?array $schema): array
    {
        $columns = $schema['columns'] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        $editable = [];
        foreach ($columns as $columnId => $columnDefinition) {
            if (! is_array($columnDefinition)) {
                continue;
            }
            if (($columnDefinition['editable'] ?? false) !== true) {
                continue;
            }
            $id = trim((string) ($columnDefinition['id'] ?? $columnId));
            if ($id === '') {
                continue;
            }
            $editable[$id] = true;
        }

        return $editable;
    }
}
