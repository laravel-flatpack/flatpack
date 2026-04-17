<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;

interface FlatpackAuthorizer
{
    /**
     * Whether the user may access the Flatpack admin area at all.
     *
     * @param  Authenticatable|null  $user  The user to check.
     * @return bool Whether the user may access the Flatpack admin area at all.
     */
    public function canAccessPanel(?Authenticatable $user): bool;

    /**
     * Whether the user may perform a policy ability on a model class.
     * Optional Model instance for "update"/"delete".
     *
     * @param  Authenticatable  $user  The user to check.
     * @param  string  $ability  The ability to check, e.g. "create", "update", "delete".
     * @param  string  $modelClass  The model class to check.
     * @param  object|null  $model  The model instance to check.
     * @return bool Whether the user may perform the action on the model.
     */
    public function allows(
        ?Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool;
}
