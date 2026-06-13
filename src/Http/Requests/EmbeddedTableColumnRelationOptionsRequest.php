<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Http\Requests\Concerns\AuthorizesRelationOptions;
use Flatpack\Services\Forms\RelationOptionsAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

final class EmbeddedTableColumnRelationOptionsRequest extends FormRequest
{
    use AuthorizesRelationOptions;

    public function authorize(): bool
    {
        $entity = trim((string) $this->route('entity', ''));
        $tableFieldId = trim((string) $this->query('table_field', ''));
        $columnId = trim((string) $this->query('column_id', ''));
        if ($entity === '' || $tableFieldId === '' || $columnId === '') {
            return true;
        }

        $relatedModelClass = $this->container
            ->make(RelationOptionsAuthorizer::class)
            ->relatedModelClassForEmbeddedTableColumn($entity, $tableFieldId, $columnId);

        return $this->authorizeRelationOptionsForRelatedModel($this->user(), $relatedModelClass);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'table_field' => ['required', 'string', 'max:255'],
            'column_id' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string'],
            'selected' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
