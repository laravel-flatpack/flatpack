<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;

final class DenyViewPostPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Post $post): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return true;
    }

    public function delete(User $user, Post $post): bool
    {
        return true;
    }
}
