<?php

namespace Flatpack\Traits;

use Illuminate\Support\Arr;

trait WithComposition
{
    /**
     * Array representation of the view.
     *
     * @var array
     */
    public $composition = [];

    /**
     * Form type.
     *
     * @var string
     */
    public $formType;

    /**
     * Get composition by key.
     *
     * @param  string  $key
     * @return array
     */
    protected function getComposition($key)
    {
        $composition = Arr::get($this->composition, $key, []);

        return collect($composition)->filter(function ($item) {
            return in_array(
                $this->formType,
                collect(Arr::get($item, 'form', [
                    null,
                    'view',
                    'create',
                    'edit',
                ]))->toArray()
            );
        })->toArray();
    }

    /**
     * Get the main composition.
     *
     * @return array
     */
    protected function getMainComposition()
    {
        $main = $this->getComposition('main');

        if (empty($main)) {
            $main = $this->getComposition('fields');
        }

        return $main;
    }

    /**
     * Recursively get all the form fields in a composition.
     *
     * @param  array  $composition
     * @return array
     */
    protected function getCompositionFields($composition)
    {
        $fields = [];

        foreach ($composition as $key => $options) {
            if ($key === 'tabs') {
                foreach ($options as $tab) {
                    $tabFields = Arr::get($tab, 'fields', []);
                    $fields = array_merge(
                        $fields,
                        $this->getCompositionFields($tabFields)
                    );
                }
            } else {
                $fields = array_merge($fields, [$key => $options]);
            }
        }

        return $fields;
    }

    /**
     * Get all the form fields.
     *
     * @return array
     */
    protected function getAllCompositionFields()
    {
        return array_merge(
            $this->getCompositionFields($this->getComposition('header')),
            $this->getCompositionFields($this->getMainComposition()),
            $this->getCompositionFields($this->getComposition('sidebar'))
        );
    }
}
