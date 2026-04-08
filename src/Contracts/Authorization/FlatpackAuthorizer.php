<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;

interface FlatpackAuthorizer
{
    /**
     * Whether the user may access the Flatpack admin area at all.
     */
    public function canAccessPanel(?Authenticatable $user): bool;

    /**
     * Whether the user may perform a policy ability on a model class (optional instance for "update"/"delete").
     */
    public function authorizeModelAbility(
        ?Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool;
}
