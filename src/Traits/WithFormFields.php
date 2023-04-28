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
     * Change form fields.
     *
     * @var array
     */
    public $changedFields = [];

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
        "html",
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
                ! isset($this->fields[$key]) ||
                $this->fields[$key] === null
            ) {
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
            ->filter(fn ($options) => ! in_array(data_get($options, "type"), $this->excludedTypes))
            ->all();
    }

    /**
     * Get form fields compositions with preset options.
     *
     * @return array
     */
    protected function onlyPresetFields()
    {
        return collect($this->onlyInputFields())
            ->filter(fn ($item) => data_get($item, 'preset'))
            ->all();
    }

    /**
     * Return the clean field key name.
     *
     * @param  string $key
     * @return string
     */
    protected function fieldKeyName($name)
    {
        return str_replace('fields.', '', $name);
    }

    /**
     * Apply preset value.
     *
     * @param string $key
     * @param string $data
     * @return void
     */
    protected function applyPreset($key, $data)
    {
        foreach (collect($data) as $value) {
            $presets = [
                'exact' => Str::of($value)->toString(),
                'slug' => Str::of($value)->slug()->toString(),
            ];

            $fields = collect($this->onlyPresetFields())
                ->filter(fn ($item) => data_get($item, 'preset.field') === $key)
                ->all();

            foreach ($fields as $field => $options) {
                if (is_null(data_get($this->fields, $field)) && ! $this->hasChanges($field)) {
                    $value = $presets[data_get($options, 'preset.type', 'exact')];
                    $this->fields[$field] = $value;
                    $this->changedField($field, $value);
                }
            }
        }
    }

    /**
     * Set hasChanges flag if field has changed.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function changedField($key, $value)
    {
        $field = $this->fieldKeyName($key);

        $this->fields[$field] = $value;

        $this->changedFields = collect($this->changedFields)
            ->add($field)
            ->unique()
            ->all();
    }

    /**
     * Apply preset value.
     *
     * @param string $field
     * @return bool
     */
    protected function hasChanges($field)
    {
        return collect($this->changedFields)->has($field);
    }
}
