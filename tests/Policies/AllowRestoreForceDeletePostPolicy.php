<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;

final class AllowRestoreForceDeletePostPolicy
{
    public function restore(User $user, Post $post): bool
    {
        return true;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return true;
    }
}
