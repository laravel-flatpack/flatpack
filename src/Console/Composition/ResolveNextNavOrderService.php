<?php

declare(strict_types=1);

namespace Flatpack\Console\Composition;

use Flatpack\Navigation\FlatpackMenuBuilder;
use Flatpack\Navigation\MenuItem;
use InvalidArgumentException;

final readonly class ResolveNextNavOrderService
{
    private const array ALLOWED_MENUS = ['main', 'secondary', 'bottom'];

    public function __construct(
        private FlatpackMenuBuilder $menuBuilder,
    ) {}

    public function resolve(string $menu): int
    {
        $normalizedMenu = trim($menu);
        if (! in_array($normalizedMenu, self::ALLOWED_MENUS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid [--menu] value "%s". Allowed: main, secondary, bottom.',
                $menu
            ));
        }

        $items = $this->menuBuilder->resolveMenuItemsByBucket(applyAuthorization: false)[$normalizedMenu];
        if ($items === []) {
            return 100;
        }

        return $this->maxNavOrder($items) + 10;
    }

    /**
     * @param  list<MenuItem>  $items
     */
    private function maxNavOrder(array $items): int
    {
        return array_reduce(
            $items,
            static fn (int $carry, MenuItem $item): int => max($carry, $item->navOrder),
            0,
        );
    }
}
