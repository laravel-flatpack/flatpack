<?php

declare(strict_types=1);

namespace Flatpack\Services\Navigation;

interface MenuBuilder
{
    /**
     * @return list<MenuItem>
     */
    public function build(): array;
}
