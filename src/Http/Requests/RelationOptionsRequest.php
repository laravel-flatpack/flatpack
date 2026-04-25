<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RelationOptionsRequest extends FormRequest
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
            'field' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string'],
            'selected' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
