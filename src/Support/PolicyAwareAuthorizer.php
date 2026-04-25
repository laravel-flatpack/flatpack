<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class PolicyAwareAuthorizer implements FlatpackAuthorizer
{
    public function canAccessPanel(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'canAccessFlatpack')) {
            return false;
        }

        return (bool) call_user_func([$user, 'canAccessFlatpack']);
    }

    public function allows(
        ?Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool {
        if ($user === null) {
            return false;
        }

        $target = $model ?? new $modelClass;

        if (Gate::getPolicyFor($target) === null) {
            return (bool) config('flatpack.authorization.allow_when_policy_missing', false)
                && $this->canAccessPanel($user);
        }

        return Gate::forUser($user)->check($ability, $target);
    }
}
