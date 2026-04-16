<?php

declare(strict_types=1);

namespace Flatpack\Menu;

/** @deprecated Use Flatpack\Menu\FlatpackMenuBuilder instead. */
final readonly class MenuBuilder
{
    public function build(): array
    {
        return app(FlatpackMenuBuilder::class)->build();
    }
}
