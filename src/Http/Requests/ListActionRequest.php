<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST payloads for list header actions and row actions.
 *
 * @see \Flatpack\Http\Controllers\EntityActionController::listAction
 * @see \Flatpack\Http\Controllers\EntityActionController::rowAction
 */
final class ListActionRequest extends FormRequest
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
        ];
    }
}
