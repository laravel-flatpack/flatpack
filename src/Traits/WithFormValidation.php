<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Facades\Validator;

trait WithFormValidation
{
    protected function validateForm($fields = [], $form = [])
    {
        return Validator::make(
            $fields,
            $this->getValidationRules($form)
        )->validate();
    }

    protected function getValidationRules($fields = [])
    {
        $rules = [];

        foreach ($fields as $key => $options) {
            $rules[$key] = $options['rules'] ?? 'present';
        }

        return $rules;
    }
}
