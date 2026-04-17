<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Composition\EntityComposition;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Schema\FormFieldType;
use Flatpack\Schema\HeaderActions;
use Flatpack\Services\Actions\ActionRuntime;
use Flatpack\Support\SuccessRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

final readonly class FormController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private ActionRuntime $actions,
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
            'form_actions' => HeaderActions::fromSchema($schema),
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
            'form_actions' => HeaderActions::fromSchema($schema),
        ], $request->boolean('json'));
    }

    public function save(
        FormSubmitRequest $request,
        string $entity,
        ?string $record = null,
    ): RedirectResponse {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $modelClass = (string) ($form->model ?? '');
        $model = $record !== null
            ? $this->actions->resolveRecordModel($modelClass, $record, 'form')
            : null;
        $handler = $this->actions->resolveRecordActionHandler('save');

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
            throw $this->actions->toUserFacingValidationException($exception);
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

        $savedKey = (string) $savedModel->getKey();
        $target = SuccessRedirect::fromFormSchema($schema);
        if ($target === null) {
            return redirect()->route('flatpack.entities.edit', [
                'entity' => $entity,
                'record' => $savedKey,
            ])->setStatusCode(303);
        }

        return SuccessRedirect::responseForFormSave(
            $target,
            $entity,
            $record === null,
            $savedKey,
        );
    }

    private function resolveOptionalRecordModel(
        string $modelClass,
        string $record,
    ): ?Model {
        return $this->actions->resolveOptionalRecordModel($modelClass, $record);
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
        $rawType = isset($fieldDefinition['type'])
            ? trim((string) $fieldDefinition['type'])
            : '';

        $fieldDefinition['type'] = FormFieldType::normalizeYamlType($rawType);

        if ($rawType === 'relation') {
            $fieldDefinition['type'] = 'combobox';
            $fieldDefinition['options'] = [];
            $fieldDefinition['remote'] = true;
        } elseif ($rawType === 'select' || $rawType === 'combobox') {
            $fieldDefinition['options'] = $this->normalizeFieldOptions(
                $fieldDefinition['options'] ?? null,
            );
        }

        return $fieldDefinition;
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
}
