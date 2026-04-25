<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EmbeddedTableColumnRelationOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'table_field' => ['required', 'string', 'max:255'],
            'column_id' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'string'],
            'selected' => ['sometimes', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
