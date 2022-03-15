<?php

namespace Faustoq\Flatpack\View\Components;

class RelationField extends InputField
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
    public $type = 'relation';

    /**
     * Field items (for select, radio, checkbox).
     *
     * @var array
     */
    public $items = [];

    /**
     * Form fields values.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Form fields composition.
     *
     * @var array
     */
    public $formFields = [];

    /**
     * Field options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Relationship type.
     *
     * @var mixed
     */
    public $relationshipType = null;

    /**
     * Relationship model.
     *
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    public $relationshipModel;

    /**
     * Create a new related model instance.
     *
     * @var bool
     */
    public $canCreate = false;

    public function __construct(
        $key,
        $options,
        $entity = '',
        $model = '',
        $entry = null,
        $fieldErrors = [],
        $formType = 'create'
    ) {
        parent::__construct($key, $options, $entity, $model, $entry, $fieldErrors, $formType);

        $this->initRelationshipProps();
    }

    protected function initRelationshipProps(): void
    {
        $field = $this->getOption('relation.name', $this->key);
        $display = $this->getOption('relation.display', 'name');
        $this->canCreate = $this->getOption('relation.make', false);

        $this->relationshipType = $this->getRelationshipType($field);
        $this->relationshipModel = $this->getRelationshipModel($this->key);
        $this->items = $this->getRelationshipItems($field, $display);

        if ($this->canCreate) {
            $this->formFields = $this->getOption('relation.fields', []);

            foreach ($this->formFields as $key => $options) {
                $this->fields[$key] = null;
            }
        }
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flatpack::components.relation-field');
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'span' => $this->span,
            'type' => $this->type,
            'items' => $this->items,
            'readonly' => $this->readonly,
            'disabled' => $this->disabled,
            'required' => $this->required,
            'value' => $this->value,
        ];
    }
}
