<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Actions\Handlers\SaveRecordHandler;
use Flatpack\Composition\EntityComposition;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Lists\ListHeaderActions;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Throwable;

final readonly class FormController
{
    public function __construct(
        private EntityComposition $entityComposition,
    ) {}

    public function create(Request $request, string $entity): Response|JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $normalizedSchema = $this->normalizedFormSchema(
            $schema,
            (string) ($form->model ?? ''),
        );

        return FlatpackResponse::inertia('form', [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => null,
            'mode' => 'create',
            'schema' => $normalizedSchema,
            'values' => [],
            'form_actions' => ListHeaderActions::fromSchema($schema),
        ], $request->boolean('json'));
    }

    public function edit(Request $request, string $entity, string $record): Response|JsonResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $normalizedSchema = $this->normalizedFormSchema(
            $schema,
            (string) ($form->model ?? ''),
        );
        $model = $this->resolveOptionalRecordModel((string) ($form->model ?? ''), $record);

        return FlatpackResponse::inertia('form', [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => 'edit',
            'schema' => $normalizedSchema,
            'values' => $this->formValuesFromModel($model, $schema),
            'form_actions' => ListHeaderActions::fromSchema($schema),
        ], $request->boolean('json'));
    }

    public function save(
        Request $request,
        string $entity,
        ?string $record = null,
    ): RedirectResponse {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $modelClass = (string) ($form->model ?? '');
        $model = $record !== null ? $this->resolveRecordModel($modelClass, $record) : null;
        $handler = $this->resolveRecordActionHandler('save');

        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: 'save',
                modelClass: $modelClass,
                record: $record,
                compositionType: 'form',
                composition: $schema ?? [],
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            throw $this->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        $savedModel = $result instanceof Model ? $result : $model;
        if (! $savedModel instanceof Model || $savedModel->getKey() === null) {
            return back(303)->with('flatpack', [
                'save' => true,
            ]);
        }

        return redirect()->route('flatpack.entities.edit', [
            'entity' => $entity,
            'record' => $savedModel->getKey(),
        ])->setStatusCode(303);
    }

    private function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            $handlerClass = match ($action) {
                'save' => SaveRecordHandler::class,
                default => null,
            };
        }
        if (! is_string($handlerClass)) {
            abort(404, 'Flatpack action handler is not configured.');
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackAction) {
            abort(500, 'Flatpack action handler must implement FlatpackAction.');
        }

        return $handler;
    }

    private function resolveRecordModel(string $modelClass, string $record): Model
    {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            abort(404, 'Flatpack form model is not configured.');
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            abort(404, 'Flatpack form model class is invalid.');
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = $model->getKeyName();

        return $modelClass::query()
            ->where($keyName, $record)
            ->firstOrFail();
    }

    private function resolveOptionalRecordModel(
        string $modelClass,
        string $record,
    ): ?Model {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = $model->getKeyName();

        return $modelClass::query()
            ->where($keyName, $record)
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function formValuesFromModel(?Model $model, ?array $schema): array
    {
        if (! $model instanceof Model || $schema === null) {
            return [];
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        $values = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                continue;
            }

            $id = trim((string) ($fieldDefinition['id'] ?? $fieldId));
            if ($id === '') {
                continue;
            }

            $values[$id] = $model->getAttribute($id);
        }

        return $values;
    }

    private function toUserFacingValidationException(
        Throwable $exception,
    ): ValidationException {
        report($exception);

        $message = 'This change could not be completed.';
        if ($exception instanceof MassAssignmentException) {
            $message = config('app.debug')
                ? $exception->getMessage()
                : 'This field is not writable for this model.';
        } elseif (config('app.debug')) {
            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $message;
        }

        return ValidationException::withMessages([
            'flatpack' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>|null
     */
    private function normalizedFormSchema(?array $schema, string $modelClass): ?array
    {
        if ($schema === null) {
            return null;
        }

        $fields = $schema['fields'] ?? null;
        if (! is_array($fields)) {
            return $schema;
        }

        $normalizedFields = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                $normalizedFields[$fieldId] = $fieldDefinition;

                continue;
            }

            $normalizedFields[$fieldId] = $this->normalizedFieldDefinition(
                $fieldDefinition,
                $modelClass,
            );
        }

        $normalized = $schema;
        $normalized['fields'] = $normalizedFields;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return array<string, mixed>
     */
    private function normalizedFieldDefinition(
        array $fieldDefinition,
        string $modelClass,
    ): array {
        $type = isset($fieldDefinition['type'])
            ? trim((string) $fieldDefinition['type'])
            : '';

        if ($type === 'date') {
            $fieldDefinition['type'] = 'date-picker';
        }

        if ($type === 'relation') {
            $fieldDefinition['type'] = 'combobox';
            $fieldDefinition['options'] = $this->relationFieldOptions(
                $modelClass,
                $fieldDefinition,
            );
        } elseif ($type === 'select' || $type === 'combobox') {
            $fieldDefinition['options'] = $this->normalizeFieldOptions(
                $fieldDefinition['options'] ?? null,
            );
        }

        return $fieldDefinition;
    }

    /**
     * @param  array<string, mixed>  $fieldDefinition
     * @return list<array{value: string, label: string}>
     */
    private function relationFieldOptions(
        string $modelClass,
        array $fieldDefinition,
    ): array {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return [];
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $relationName = isset($fieldDefinition['relation'])
            ? trim((string) $fieldDefinition['relation'])
            : '';
        $labelField = $this->stringFromField($fieldDefinition, 'relation_name', 'relationName');
        $valueField = $this->stringFromField($fieldDefinition, 'relation_value', 'relationValue');

        if ($relationName === '' || $labelField === '' || $valueField === '') {
            return [];
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        if (! method_exists($model, $relationName)) {
            return [];
        }

        $relation = $model->{$relationName}();
        if (! $relation instanceof Relation) {
            return [];
        }

        return $relation->getRelated()
            ->newQuery()
            ->orderBy($labelField)
            ->get([$valueField, $labelField])
            ->map(fn (Model $related): array => [
                'value' => (string) $related->getAttribute($valueField),
                'label' => (string) $related->getAttribute($labelField),
            ])
            ->filter(fn (array $option): bool => $option['value'] !== '' && $option['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string, status?: string, icon?: string}>
     */
    private function normalizeFieldOptions(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        if (array_is_list($raw)) {
            foreach ($raw as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $value = isset($option['value']) ? trim((string) $option['value']) : '';
                $label = isset($option['label']) ? trim((string) $option['label']) : '';
                if ($value === '' || $label === '') {
                    continue;
                }

                $normalized = ['value' => $value, 'label' => $label];
                if (isset($option['status']) && is_string($option['status']) && trim($option['status']) !== '') {
                    $normalized['status'] = trim($option['status']);
                }
                if (isset($option['icon']) && is_string($option['icon']) && trim($option['icon']) !== '') {
                    $normalized['icon'] = trim($option['icon']);
                }
                $out[] = $normalized;
            }

            return $out;
        }

        foreach ($raw as $value => $label) {
            if (! is_string($label)) {
                continue;
            }

            $normalizedValue = trim((string) $value);
            $normalizedLabel = trim($label);
            if ($normalizedValue === '' || $normalizedLabel === '') {
                continue;
            }

            $out[] = [
                'value' => $normalizedValue,
                'label' => $normalizedLabel,
            ];
        }

        return $out;
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
