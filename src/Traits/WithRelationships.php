<?php

namespace Faustoq\Flatpack\Traits;

trait WithRelationships
{
    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $entry;

    /**
     * Get relationship method.
     *
     * @var mixed
     */
    private function relation($key)
    {
        return ($this->isRelationship($key)) ? $this->entry->{$key}() : null;
    }

    /**
     * Get the items to populate relationship select.
     *
     * @return array
     */
    protected function getRelationshipItems($field, $display = 'name')
    {
        $values = [];

        $relation = $this->relation($field);

        if ($this->getRelationshipType($field) === 'belongsToMany') {
            $values = $relation->getRelated()->get()->pluck(
                $display,
                $relation->getRelatedKeyName()
            );
        }

        if ($this->getRelationshipType($field) === 'belongsTo') {
            $values = $relation->getRelated()->get()->pluck(
                $display,
                $relation->getOwnerKeyName()
            );
        }

        return $values;
    }

    protected function createRelationship($key, $name = 'name', $value = null)
    {
        $relation = $this->relation($key);
        $relatedClass = get_class($relation->getRelated());
        $model = new $relatedClass;
        $model->{$name} = $value;
        $model->save();

        return $model->getKey();
    }

    /**
     * Sync field relationship.
     *
     * @param string $key
     * @param bool $canCreate
     * @return void
     */
    protected function syncRelationship($key)
    {
        $relation = $this->relation($key);
        $related = $relation->getRelated();

        /**
         * Relation: Belongs To
         *
         * Save related model using associate method:
         * https://laravel.com/docs/8.x/eloquent-relationships#updating-belongs-to-relationships
         *
         */
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $this->entry->{$key}()->associate(
                $related->find($this->fields[$key])
            );
        } else {
            $this->entry->{$key}()->sync($this->fields[$key]);
        }
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    protected function getRelationshipType($field)
    {
        $relation = $this->relation($field);

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

    /**
     * Check if the field is a relationship.
     *
     * @return bool
     */
    protected function isRelationship($key)
    {
        if (method_exists($this->entry, $key)) {
            return in_array(
                get_class($this->entry->{$key}()),
                $this->allowedRelationships()
            );
        }

        return false;
    }

    /**
     * List of allowed relationships.
     *
     * @return array
     */
    private function allowedRelationships()
    {
        return [
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            \Illuminate\Database\Eloquent\Relations\MorphOne::class,
            \Illuminate\Database\Eloquent\Relations\MorphTo::class,
            \Illuminate\Database\Eloquent\Relations\MorphToMany::class,
        ];
    }
}
