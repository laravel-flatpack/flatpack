<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait WithFormFields
{
    /**
     * Bind model to fields.
     *
     * @return void
     */
    protected function bindModelToFields()
    {
        foreach ($this->getFormFields() as $key => $options) {
            if ($this->entry->$key instanceof \Illuminate\Support\Carbon) {
                $this->fields[$key] = $this->entry->$key->format('Y-m-d\TH:i:s');
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Collection) {
                $id = optional($this->relation($key))->getRelatedKeyName();
                $this->fields[$key] = $this->entry->$key->pluck($id)->toArray();
            } elseif ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Model) {
                $this->fields[$key] = $this->entry->$key->getKey();
            } else {
                $this->fields[$key] = $this->entry->$key;
            }
        }
    }

    /**
     * Bind fields to model.
     *
     * @return void
     */
    protected function bindFieldsToModel()
    {
        $fields = $this->getFormFields();

        foreach ($fields as $key => $options) {
            if ((isset($options['type']) && in_array($options['type'], $this->excludedTypes)) ||
                (isset($options['disabled']) && $options['disabled'] === true) ||
                (isset($options['readonly']) && $options['readonly'] === true) ||
                Str::contains($key, '_confirmation') ||
                $this->isRelationship($key) ||
                $this->fields[$key] === null) {
                continue;
            }

            $this->entry->{$key} = $this->fields[$key];
        }
    }
}
