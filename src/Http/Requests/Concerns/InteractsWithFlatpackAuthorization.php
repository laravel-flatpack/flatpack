<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

trait InteractsWithFlatpackAuthorization
{
    /**
     * @return false|never
     */
    protected function denyFlatpackAuthorization(string $debugMessage): bool
    {
        if ($this->shouldExplainFlatpackAuthorizationDenial()) {
            throw new AuthorizationException($debugMessage);
        }

        return false;
    }

    /**
     * @return false|never
     */
    protected function denyFlatpackGateAuthorization(
        Authenticatable $user,
        string $ability,
        object $target,
    ): bool {
        if ($this->shouldExplainFlatpackAuthorizationDenial()) {
            $response = Gate::forUser($user)->inspect($ability, $target);
            $message = $response->message();
            $fallback = sprintf(
                'Gate denied "%s" for %s. Register or adjust a policy that allows this ability.',
                $ability,
                class_basename($target::class),
            );

            throw new AuthorizationException(
                ($message !== null && $message !== '') ? $message : $fallback,
            );
        }

        return false;
    }

    private function shouldExplainFlatpackAuthorizationDenial(): bool
    {
        return (bool) config('app.debug');
    }
}
