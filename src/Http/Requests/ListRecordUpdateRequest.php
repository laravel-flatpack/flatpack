<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Composition\EntityComposition;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Http\Requests\Concerns\InteractsWithFlatpackAuthorization;
use Flatpack\Schema\Validation\ListSchemaRuleBuilder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Override;

/**
 * Validates inline PATCH updates from the list UI (policy checks run in {@see SaveRecordHandler::authorize}).
 */
final class ListRecordUpdateRequest extends FormRequest
{
    use InteractsWithFlatpackAuthorization;

    public function authorize(): bool
    {
        $authorizer = $this->container->make(FlatpackAuthorizer::class);
        $user = $this->user();

        if ($user === null) {
            return $this->denyFlatpackAuthorization('You must be logged in to update this record.');
        }

        if (! $authorizer->canAccessPanel($user)) {
            return $this->denyFlatpackAuthorization(
                ! method_exists($user, 'canAccessFlatpack')
                    ? 'Flatpack requires your User model to implement canAccessFlatpack(): bool.'
                    : 'canAccessFlatpack() returned false — panel access is denied.',
            );
        }

        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return $this->denyFlatpackAuthorization('The route is missing the entity name.');
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $list = $entityComposition->listFor($entity);
        $modelClass = trim((string) ($list->model ?? ''));
        if ($modelClass === '') {
            return $this->denyFlatpackAuthorization(
                sprintf('Flatpack list.yaml for entity "%s" must declare a model.', $entity),
            );
        }

        $record = trim((string) $this->route('record', ''));
        if ($record === '') {
            return $this->denyFlatpackAuthorization('The route is missing the record id.');
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return [
                'values' => 'prohibited',
                'field' => 'prohibited',
                'value' => 'prohibited',
            ];
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $list = $entityComposition->listFor($entity);
        $modelClass = trim((string) ($list->model ?? ''));
        $schema = $entityComposition->listSchema($entity);

        $builder = $this->container->make(ListSchemaRuleBuilder::class);
        $valueRules = $builder->rulesForValues($schema, $modelClass);

        return array_merge(
            [
                'values' => ['nullable', 'array'],
                'field' => ['nullable', 'string', 'max:255'],
                'value' => ['nullable'],
            ],
            $valueRules,
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $values = $this->input('values');
            if (! is_array($values)) {
                return;
            }

            $allowedFields = $this->editableListFieldIds();

            foreach ($values as $field => $_value) {
                if (! is_string($field)) {
                    continue;
                }
                $id = trim($field);
                if ($id === '' || in_array($id, $allowedFields, true)) {
                    continue;
                }

                $validator->errors()->add(
                    'values.' . $id,
                    sprintf('Field "%s" is not editable for this list.', $id),
                );
            }
        });
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('values')) {
            return;
        }

        $field = trim((string) $this->input('field', ''));
        if ($field !== '') {
            $this->merge([
                'values' => [$field => $this->input('value')],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function editableListFieldIds(): array
    {
        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return [];
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $list = $entityComposition->listFor($entity);
        $modelClass = trim((string) ($list->model ?? ''));
        $schema = $entityComposition->listSchema($entity);
        $builder = $this->container->make(ListSchemaRuleBuilder::class);
        $valueRules = $builder->rulesForValues($schema, $modelClass);

        $fields = [];
        foreach (array_keys($valueRules) as $ruleKey) {
            if (! is_string($ruleKey) || ! str_starts_with($ruleKey, 'values.')) {
                continue;
            }
            $id = mb_substr($ruleKey, mb_strlen('values.'));
            if ($id === '') {
                continue;
            }
            $fields[] = $id;
        }

        return $fields;
    }
}
