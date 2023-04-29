<?php

namespace Flatpack\View\Components;

class TagInput extends RelationField
{
    /**
     * Form field name.
     *
     * @var string
     */
    public $key;

    /**
     * Field type.
     *
     * @var string
     */
    public $type = 'tag-input';

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flatpack::components.tag-input')
            ->with('source', $this->getTagInputSource())
            ->with('tagInputItems', $this->getTagInputItems());
    }

    /**
     * Url for tag input suggestions.
     *
     * @return string
     */
    private function getTagInputSource()
    {
        $entity = $this->getFieldOption('relation.entity', $this->getFieldOption('relation.name'));
        $search = $this->getFieldOption('relation.display', 'name');

        return flatpackUrl("/api/suggestions/{$entity}?display={$search}");
    }

    /**
     * Tag input initial value items.
     *
     * @return string
     */
    private function getTagInputItems()
    {
        return collect($this->value)
            ->map(fn ($value) => [
                'value' => $value->{$value->getKeyName()},
                'name' => $value->{$this->getFieldOption('relation.display', 'name')},
                'exists' => true,
            ])
            ->toArray();
    }
}
