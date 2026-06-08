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
 * Validates POST payloads for {@see \Flatpack\Http\Controllers\EntityActionController::bulkAction}.
 *
 * Selection may be {@code all} or a list of ids under {@code selection}, plus optional filters/search.
 */
final class BulkActionRequest extends FormRequest
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
        $allowed = $this->allowedBulkActionStrings();

        return [
            'action' => ['required', 'string', 'max:255', Rule::in($allowed)],
            'selection' => ['nullable'],
            'filters' => ['nullable', 'array'],
            'search' => ['nullable', 'string', 'max:65535'],
            'tab' => ['nullable', 'string', 'max:255'],
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
    private function allowedBulkActionStrings(): array
    {
        if ($this->routeIs('flatpack.dashboard.widgets.bulk-action')) {
            $widget = trim((string) $this->route('widget', ''));

            return ListSubmitActionAllowed::allowedWidgetBulkActionStrings(
                $this->resolveDashboardWidgetDefinition($widget),
            );
        }

        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return [];
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $listSchema = $entityComposition->listSchema($entity);
        $tab = trim((string) $this->input('tab', ''));

        return ListSubmitActionAllowed::allowedBulkActionStrings(
            $listSchema,
            $tab !== '' ? $tab : null,
        );
    }
}
