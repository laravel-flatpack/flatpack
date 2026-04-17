<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

final class SaveRecordHandler extends FlatpackActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        if ($this->modelExists($model)) {
            return $this->authorizer()->allows(
                user: $user,
                ability: 'update',
                modelClass: $modelClass,
                model: $model,
            );
        }

        return $this->authorizer()->allows(
            user: $user,
            ability: 'create',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $this->resolveModel($context);
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

        $writableFields = $this->writableFieldsFromSchema(
            compositionType: $context->compositionType,
            schema: $context->schema,
        );
        $filtered = [];

        foreach ($values as $field => $value) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            if (! isset($writableFields[$field])) {
                continue;
            }
            if (! $model->isFillable($field)) {
                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $field,
                    $model::class,
                ));
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

    private function resolveModel(FlatpackActionContext $context): ?Model
    {
        if ($context->model instanceof Model) {
            return $context->model;
        }

        $modelClass = trim($context->modelClass);
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return new $modelClass();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, true>
     */
    private function writableFieldsFromSchema(
        string $compositionType,
        ?array $schema,
    ): array {
        if ($schema === null) {
            return [];
        }

        return match ($compositionType) {
            'form' => $this->formFieldsFromSchema($schema),
            'list' => $this->editableListColumnsFromSchema($schema),
            default => $this->editableListColumnsFromSchema($schema),
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, true>
     */
    private function editableListColumnsFromSchema(array $schema): array
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

    /**
     * Form schemas are expected to describe writable fields directly under
     * `fields`, so the presence of a field definition opts it into the save
     * action. This keeps the handler ready for future form submissions while
     * the model `fillable` contract remains the final write guard.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, true>
     */
    private function formFieldsFromSchema(array $schema): array
    {
        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $writable = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $writable[$id] = true;
        }

        return $writable;
    }
}
