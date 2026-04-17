<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests;

use Flatpack\Composition\EntityComposition;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Http\Requests\Concerns\InteractsWithFlatpackAuthorization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates inline PATCH updates from the list UI (policy checks run in {@see SaveRecordHandler::authorize}).
 */
final class ListRecordUpdateRequest extends FormRequest
{
    use InteractsWithFlatpackAuthorization;

    public function authorize(): bool
    {
        $authorizer = $this->container->make(FlatpackAuthorizer::class);
        $user = $this->user();

        if ($user === null) {
            return $this->denyFlatpackAuthorization('You must be logged in to update this record.');
        }

        if (! $authorizer->canAccessPanel($user)) {
            return $this->denyFlatpackAuthorization(
                ! method_exists($user, 'canAccessFlatpack')
                    ? 'Flatpack requires your User model to implement canAccessFlatpack(): bool.'
                    : 'canAccessFlatpack() returned false — panel access is denied.',
            );
        }

        $entity = trim((string) $this->route('entity', ''));
        if ($entity === '') {
            return $this->denyFlatpackAuthorization('The route is missing the entity name.');
        }

        $entityComposition = $this->container->make(EntityComposition::class);
        $list = $entityComposition->listFor($entity);
        $modelClass = trim((string) ($list->model ?? ''));
        if ($modelClass === '') {
            return $this->denyFlatpackAuthorization(
                sprintf('Flatpack list.yaml for entity "%s" must declare a model.', $entity),
            );
        }

        $record = trim((string) $this->route('record', ''));
        if ($record === '') {
            return $this->denyFlatpackAuthorization('The route is missing the record id.');
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'values' => 'nullable|array',
            'field' => 'nullable|string|max:255',
            'value' => 'nullable',
        ];
    }
}
