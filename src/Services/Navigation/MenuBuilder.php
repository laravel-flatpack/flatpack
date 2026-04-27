<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

interface MenuBuilder
{
    /**
     * @return array{main: list<MenuItem>, secondary: mixed, bottom: mixed}
     */
    public function resolveSharedNavigation(): array;
}
