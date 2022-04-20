<?php

namespace Flatpack\Traits;

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
    protected function relation($key)
    {
        return ($this->isRelationship($key)) ? $this->entry->{$key}() : null;
    }

    /**
     * Create a new related model instance.
     *
     * @param $key
     * @param $name
     * @param $value
     * @return mixed
     */
    protected function createRelationship($key, $name = 'name', $value = null)
    {
        $relation = $this->relation($key);
        $relatedClass = get_class($relation->getRelated());
        $model = new $relatedClass();
        $model->{$name} = $value;
        $model->save();

        return $model->getKey();
    }

    /**
     * Sync field relationship.
     *
     * @param string $key
     * @return void
     */
    protected function syncRelationship($key)
    {
        if (! $this->isRelationship($key)) {
            return;
        }

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
     * Check if the field is a relationship.
     *
     * @return bool
     */
    protected function isRelationship($key)
    {
        if (! $this->entry) {
            return false;
        }

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
