<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
        ];
    }
}
