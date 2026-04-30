<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

use Illuminate\Contracts\Auth\Authenticatable;

interface MenuBuilder
{
    /**
     * @return array{main: list<MenuItem>, secondary: mixed, bottom: mixed}
     */
    public function resolveSharedNavigation(?Authenticatable $user = null): array;
}
