<?php

namespace Faustoq\Flatpack\Traits;

use Illuminate\Support\Arr;

trait WithComposition
{
    public $composition = [];

    protected function getComposition($value)
    {
        return Arr::get($this->composition, $value, []);
    }

    protected function getMainComposition()
    {
        $main = $this->getComposition('main');

        if (empty($main)) {
            $main = $this->getComposition('fields');
        }

        return $main;
    }

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

    protected function getFormFields()
    {
        return array_merge(
            $this->getCompositionFields($this->getComposition('header')),
            $this->getCompositionFields($this->getMainComposition()),
            $this->getCompositionFields($this->getComposition('sidebar'))
        );
    }
}
