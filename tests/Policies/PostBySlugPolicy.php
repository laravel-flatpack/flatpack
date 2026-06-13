<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\PostBySlug;
use Flatpack\Tests\Models\User;

final class PostBySlugPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PostBySlug $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PostBySlug $post): bool
    {
        return true;
    }

    public function delete(User $user, PostBySlug $post): bool
    {
        return true;
    }
}
