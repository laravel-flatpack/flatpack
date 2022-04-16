<?php

namespace Flatpack\Traits;

use Illuminate\Support\Str;

trait WithFormFields
{
    /**
     * Form fields composition.
     *
     * @var array
     */
    public $formFields = [];

    /**
     * Form fields values.
     *
     * @var array
     */
    public $fields = [];

    /**
    * Model instance.
    *
    * @var \Illuminate\Database\Eloquent\Model
    */
    public $entry;

    /**
     * Field types excluded from validation and binding.
     *
     * @var array
     */
    protected $excludedTypes = [
        "actions",
        "toolbar",
        "button",
        "label",
        "heading",
        "divider",
        "html"
    ];

    /**
     * Bind model to fields.
     *
     * @return void
     */
    protected function bindModelToFields()
    {
        foreach ($this->onlyInputFields() as $key => $options) {
            if ($this->entry->$key instanceof \Illuminate\Database\Eloquent\Collection) {
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
        foreach ($this->onlyInputFields() as $key => $options) {
            if (data_get($options, "disabled") === true ||
                data_get($options, "readonly") === true ||
                Str::contains($key, "_confirmation") ||
                $this->isRelationship($key) ||
                $this->fields[$key] === null) {
                continue;
            }

            $this->entry->{$key} = $this->fields[$key];
        }
    }

    /**
     * Get form fields composition, excluding the field types defined in $excludedTypes.
     *
     * @return array
     */
    protected function onlyInputFields()
    {
        return collect($this->formFields)
            ->filter(function ($options) {
                return !in_array(data_get($options, "type"), $this->excludedTypes);
            })
            ->toArray();
    }
}
