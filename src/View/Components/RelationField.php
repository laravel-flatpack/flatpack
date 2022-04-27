<?php

namespace Flatpack\View\Components;

use Flatpack\Traits\WithRelationships;
use Illuminate\Support\Str;

class RelationField extends FormField
{
    use WithRelationships;

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
     * Model relation.
     *
     * @var null|\Illuminate\Database\Eloquent\Relations\Relation
     */
    public $relation;

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
        $fieldErrors = []
    ) {
        parent::__construct($key, $entity, $model, $options, $entry, $fieldErrors);

        $this->initRelationFieldProps();
    }

    /**
     * Initialize relation field properties.
     *
     * @return void
     */
    protected function initRelationFieldProps(): void
    {
        $relationName = $this->getFieldOption('relation.name', $this->key);

        $this->relation = $this->relation($relationName);

        $this->relationshipType = $this->getRelationshipType();

        $this->relationshipModel = $this->getRelationshipModel();

        $this->items = $this->getRelationshipItems();

        $this->canCreate = $this->getFieldOption('relation.create', false);

        if ($this->canCreate) {
            $this->setupFormFields();
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
     * Get relationship model class name.
     *
     *  @return null|string
     */
    protected function getRelationshipModel()
    {
        if (! $this->relation) {
            return null;
        }

        return $this->relation->getRelated()::class;
    }

    /**
     * Get the items to populate relationship select.
     *
     * @return array
     */
    protected function getRelationshipItems()
    {
        $select = $this->getRelationSelect();

        $display = $this->getRelationDisplay();

        $keyName = $this->getRelationKeyName();

        if ($this->getRelationshipModel() === null) {
            return [];
        }

        $query = $this->relation
                      ->getRelated()
                      ->query()
                      ->select($select);

        foreach ($this->getRelationOrderBy() as $sorting) {
            foreach ($sorting as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        return $query->get()->pluck($display, $keyName);
    }

    /**
     * Get the related model id column.
     *
     * @return array
     */
    protected function getRelationId()
    {
        return optional($this->relationshipModel)->getKeyName() ?? 'id';
    }

    /**
     * Get the related model display column.
     *
     * @return string
     */
    protected function getRelationDisplay()
    {
        return $this->getFieldOption('relation.display') ?? $this->getRelationId();
    }

    /**
     * Get the related key name.
     *
     * @return string
     */
    protected function getRelationKeyName()
    {
        if ($this->relationshipType === "belongsToMany") {
            return $this->relation->getRelatedKeyName();
        } elseif ($this->relationshipType === "belongsTo") {
            return $this->relation->getOwnerKeyName();
        } elseif (in_array($this->relationshipType, ["hasMany", "hasOne"])) {
            return $this->relation->getForeignKeyName();
        }

        return $this->getRelationId();
    }

    /**
     * Get the relationship query select columns.
     *
     * @return array
     */
    protected function getRelationSelect()
    {
        return collect($this->getRelationDisplay())
            ->add($this->getRelationId())
            ->unique()
            ->toArray();
    }

    /**
     * Get the relationship order by columns.
     *
     * @return array
     */
    protected function getRelationOrderBy()
    {
        $defaultSortColumn = $this->getRelationId();

        $orderBy = $this->getFieldOption('relation.orderBy', "$defaultSortColumn asc");

        return collect(explode(',', $orderBy))
            ->map(function ($item) {
                $item = explode(' ', trim($item));

                return [
                    $item[0] => $item[1] ?? 'asc',
                ];
            })
            ->toArray();
    }

    /**
     * Setup creation form fields.
     *
     * @return void
     */
    protected function setupFormFields()
    {
        $this->formFields = $this->getFieldOption('relation.fields', []);

        foreach ($this->formFields as $key => $options) {
            $this->fields[$key] = null;
        }
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    protected function getRelationshipType()
    {
        $relation =  Str::camel(class_basename($this->relation));

        $relations = [
            'belongsToMany',
            'belongsTo',
            'hasMany',
            'hasOne',
            'morphOne',
            'morphMany',
            'morphTo',
            'morphToMany',
        ];

        return in_array($relation, $relations) ? $relation : 'unknown';
    }
}
