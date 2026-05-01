<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST payloads for {@see \Flatpack\Http\Controllers\EntityActionController::bulkAction}.
 *
 * Selection may be {@code all} or a list of ids under {@code selection}, plus optional filters/search.
 */
final class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'max:255'],
            'selection' => ['nullable'],
            'filters' => ['nullable', 'array'],
            'search' => ['nullable', 'string', 'max:65535'],
            'tab' => ['nullable', 'string', 'max:255'],
        ];
    }
}
