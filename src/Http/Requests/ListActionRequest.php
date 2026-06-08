<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\Requests\Concerns\ResolvesDashboardWidgetDefinition;
use Flatpack\Schema\Lists\ListSubmitActionAllowed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

/**
 * Validates POST payloads for list header actions and row actions.
 *
 * @see \Flatpack\Http\Controllers\EntityActionController::listAction
 * @see \Flatpack\Http\Controllers\EntityActionController::rowAction
 */
final class ListActionRequest extends FormRequest
{
    use ResolvesDashboardWidgetDefinition;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowed = $this->allowedActionStrings();

        return [
            'action' => ['required', 'string', 'max:255', Rule::in($allowed)],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'action.required' => 'Choose an action to run.',
            'action.in' => 'This action is not allowed for this list.',
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedActionStrings(): array
    {
        if ($this->routeIs('flatpack.dashboard.widgets.row-action')) {
            $widget = trim((string) $this->route('widget', ''));

            return ListSubmitActionAllowed::allowedWidgetRowActionStrings(
                $this->resolveDashboardWidgetDefinition($widget),
            );
        }

        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return [];
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $listSchema = $entityComposition->listSchema($entity);

        if ($this->routeIs('flatpack.entities.row-action')) {
            $formSchema = $entityComposition->formSchema($entity);

            return ListSubmitActionAllowed::allowedRowActionStrings($formSchema, $listSchema);
        }

        return ListSubmitActionAllowed::allowedListHeaderActionStrings($listSchema);
    }
}
