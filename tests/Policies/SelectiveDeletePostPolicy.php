<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;

final class SelectiveDeletePostPolicy
{
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
        return ! str_starts_with((string) $post->title, 'protected');
    }
}
