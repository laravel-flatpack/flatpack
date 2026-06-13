<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Http\Requests\Concerns\AuthorizesRelationOptions;
use Flatpack\Services\Forms\RelationOptionsAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

final class RelationOptionsRequest extends FormRequest
{
    use AuthorizesRelationOptions;

    public function authorize(): bool
    {
        $entity = trim((string) $this->route('entity', ''));
        $fieldId = trim((string) $this->query('field', ''));
        if ($entity === '' || $fieldId === '') {
            return true;
        }

        $relatedModelClass = $this->container
            ->make(RelationOptionsAuthorizer::class)
            ->relatedModelClassForFormComboboxField($entity, $fieldId);

        return $this->authorizeRelationOptionsForRelatedModel($this->user(), $relatedModelClass);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string'],
            'selected' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
