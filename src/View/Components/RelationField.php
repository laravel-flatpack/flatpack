<?php

namespace Faustoq\Flatpack\View\Components;

use Faustoq\Flatpack\Traits\WithRelationships;

class RelationField extends InputField
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
        parent::__construct($key, $options, $entity, $model, $entry, $fieldErrors);

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

        $query = $this->relation->getRelated()->query()->select($select);

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
        switch (true) {
            case $this->relationshipType instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany:
                return $this->relation->getRelatedKeyName();

                break;
            case $this->relationshipType instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo:
                return $this->relation->getOwnerKeyName();

                break;
            case $this->relationshipType instanceof \Illuminate\Database\Eloquent\Relations\HasMany:
            case $this->relationshipType instanceof \Illuminate\Database\Eloquent\Relations\HasOne:
                return $this->relation->getForeignKeyName();

                break;
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
        $relation = $this->relation;

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            return 'belongsTo';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            return 'belongsToMany';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            return 'hasMany';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
            return 'hasOne';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\MorphMany) {
            return 'morphMany';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\MorphOne) {
            return 'morphOne';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
            return 'morphTo';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
            return 'morphToMany';
        }

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            return 'belongsToMany';
        }

        return 'unknown';
    }
}
