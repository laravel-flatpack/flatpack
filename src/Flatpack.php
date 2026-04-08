<?php

declare(strict_types=1);

namespace Flatpack;

use Flatpack\Contracts\Menu\MenuBuilder;
use Flatpack\Menu\MenuItem;

final readonly class Flatpack
{
    public function __construct(
        private MenuBuilder $menuBuilder,
    ) {}

    /**
     * @return list<MenuItem>
     */
    public function menu(): array
    {
        return $this->menuBuilder->build();
    }
}
