<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
            $allowWhenMissing = (bool) config('flatpack.security.authorization.allow_when_policy_missing', false);
            if ($allowWhenMissing && app()->isProduction() && ! app()->environment('local')) {
                Log::warning(sprintf(
                    'Flatpack: no policy registered for [%s] and allow_when_policy_missing=true in production. Register a policy or set FLATPACK_SECURITY_ALLOW_WHEN_POLICY_MISSING=false.',
                    $target::class,
                ));
            }

            return $allowWhenMissing && $this->canAccessPanel($user);
        }

        return Gate::forUser($user)->check($ability, $target);
    }
}
