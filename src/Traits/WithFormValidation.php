<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Facades\Validator;

trait WithFormValidation
{
    /**
     * Validate form data.
     *
     * @param array $data
     * @param array $form
     * @throws \Illuminate\Validation\ValidationException
     * @return bool
     */
    protected function validateForm($data = [], $form = [])
    {
        return Validator::make($data, $this->getValidationRules($form))
            ->validate();
    }

    /**
     * Get validation rules for all form fields.
     *
     * @param array $form
     * @return array
     */
    protected function getValidationRules($form)
    {
        $rules = [];

        foreach ($form as $key => $options) {
            $rules[$key] = $options['rules'] ?? 'present';
        }

        return $rules;
    }
}
