<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Closure;
use Flatpack\Composition\EntityComposition;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Http\Requests\Concerns\InteractsWithFlatpackAuthorization;
use Flatpack\Validation\FormSchemaRuleBuilder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class FormSubmitRequest extends FormRequest
{
    use InteractsWithFlatpackAuthorization;

    public function authorize(): bool
    {
        $authorizer = $this->container->make(FlatpackAuthorizer::class);
        $user = $this->user();

        if ($user === null) {
            return $this->denyFlatpackAuthorization('You must be logged in to save this form.');
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
        $form = $entityComposition->formFor($entity);
        $modelClass = trim((string) ($form->model ?? ''));
        if ($modelClass === '') {
            return $this->denyFlatpackAuthorization(
                sprintf('Flatpack form.yaml for entity "%s" must declare a model.', $entity),
            );
        }

        if ($this->isMethod('POST')) {
            return true;
        }

        if ($this->isMethod('PATCH')) {
            $record = trim((string) $this->route('record', ''));
            if ($record === '') {
                return $this->denyFlatpackAuthorization('The route is missing the record id.');
            }

            return true;
        }

        return $this->denyFlatpackAuthorization('Flatpack form save only supports POST (create) or PATCH (edit).');
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
            ];
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $form = $entityComposition->formFor($entity);
        $modelClass = trim((string) ($form->model ?? ''));
        $schema = $entityComposition->formSchema($entity);

        $builder = $this->container->make(FormSchemaRuleBuilder::class);
        $valueRules = $builder->rulesForValues($schema, $modelClass);

        return array_merge(
            [
                'values' => [
                    'present',
                    'array',
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if (is_array($value) && $value === []) {
                            $fail('Nothing to save');
                        }
                    },
                ],
                'form_action_id' => ['nullable', 'string', 'max:191'],
            ],
            $valueRules,
        );
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'values.present' => 'Nothing to save',
            'values.array' => 'Nothing to save',
        ];
    }
}
