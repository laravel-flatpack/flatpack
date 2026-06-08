<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Http\Requests\Concerns\AuthorizesRelationOptions;
use Flatpack\Services\Forms\RelationOptionsAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

final class DashboardWidgetRelationOptionsRequest extends FormRequest
{
    use AuthorizesRelationOptions;

    public function authorize(): bool
    {
        $widget = trim((string) $this->route('widget', ''));
        $columnId = trim((string) $this->query('column_id', ''));
        if ($widget === '' || $columnId === '') {
            return true;
        }

        $relatedModelClass = $this->container
            ->make(RelationOptionsAuthorizer::class)
            ->relatedModelClassForDashboardWidgetColumn($widget, $columnId);

        return $this->authorizeRelationOptionsForRelatedModel($this->user(), $relatedModelClass);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'column_id' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string'],
            'selected' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
