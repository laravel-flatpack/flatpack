<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class SaveRecordHandler implements FlatpackAction
{
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

        $requiredErrors = $this->requiredFieldErrorsFromSchema(
            compositionType: $context->compositionType,
            schema: $context->schema,
            values: $filtered,
        );
        if ($requiredErrors !== []) {
            throw ValidationException::withMessages($requiredErrors);
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

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function requiredFieldErrorsFromSchema(
        string $compositionType,
        ?array $schema,
        array $values,
    ): array {
        if ($compositionType !== 'form' || $schema === null) {
            return [];
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $errors = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            if (($fieldDefinition['required'] ?? false) !== true) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            if (! $this->isEmptyFormValue($values[$id] ?? null)) {
                continue;
            }

            $label = trim((string) ($fieldDefinition['label'] ?? $id));
            $errors[$id] = sprintf('%s is required.', $label !== '' ? $label : $id);
        }

        return $errors;
    }

    private function isEmptyFormValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
