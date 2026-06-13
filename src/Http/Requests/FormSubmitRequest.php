<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Closure;
use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormSidebarYamlExpander;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Http\Requests\Concerns\InteractsWithFlatpackAuthorization;
use Flatpack\Schema\Forms\FormCompositionMergeForPersistence;
use Flatpack\Schema\Forms\FormSubmitActionAllowed;
use Flatpack\Schema\Validation\FormSchemaRuleBuilder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class FormSubmitRequest extends FormRequest
{
    use InteractsWithFlatpackAuthorization;

    public function authorize(): bool
    {
        $authorizer = $this->container->make(FlatpackAuthorizer::class);
        $user = $this->user();

        if ($user === null) {
            return $this->denyFlatpackAuthorization('You must be logged in to submit this form.');
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

        if ($this->isMethod('POST') && $this->routeIs('flatpack.entities.form.submit')) {
            return true;
        }

        return $this->denyFlatpackAuthorization('Flatpack form submit must use POST {entity}/submit.');
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
        $rawSchema = $entityComposition->formSchema($entity);
        // Match FormController: inline `sidebar: fragment.yaml` before normalization so merge sees
        // the same structure as the form page (toolbars in external fragments count).
        $expandedSchema = $this->container->make(FormSidebarYamlExpander::class)->expand(
            $entity,
            is_array($rawSchema) ? $rawSchema : [],
        );
        // MergeFormTabsIntoFieldsPipe then MergeFormSidebarFieldsPipe flatten `tabs.*.fields` and
        // `sidebar` into top-level `fields`, so every `type: toolbar` (tabs, sidebar, root fields)
        // is visible to submit action allowlisting.
        $mergedSchema = FormCompositionMergeForPersistence::merge($expandedSchema) ?? $expandedSchema;

        $builder = $this->container->make(FormSchemaRuleBuilder::class);
        $valueRules = $builder->rulesForValues($expandedSchema, $modelClass);

        $allowed = FormSubmitActionAllowed::allowedActionStrings($mergedSchema);

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
                'action' => ['required', 'string', 'max:64', Rule::in($allowed)],
                'record' => ['nullable', 'string', 'max:191'],
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
            'action.required' => 'Choose an action to run.',
            'action.in' => 'This action is not allowed for this form.',
        ];
    }
}
