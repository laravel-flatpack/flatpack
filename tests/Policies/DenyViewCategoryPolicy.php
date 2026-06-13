<?php

declare(strict_types=1);

namespace Flatpack\Tests\Policies;

use Flatpack\Tests\Models\Category;
use Flatpack\Tests\Models\User;

final class DenyViewCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Category $category): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return true;
    }

    public function delete(User $user, Category $category): bool
    {
        return true;
    }
}
