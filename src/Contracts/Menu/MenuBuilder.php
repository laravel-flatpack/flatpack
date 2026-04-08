<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Menu;

use Flatpack\Menu\MenuItem;

interface MenuBuilder
{
    /**
     * @return list<MenuItem>
     */
    public function build(): array;
}
