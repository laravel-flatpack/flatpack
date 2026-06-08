<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\User;

/**
 * Policy for {@see User} in relation-options tests (distinct from Laravel's default naming).
 */
final class FlatpackUserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return true;
    }
}
