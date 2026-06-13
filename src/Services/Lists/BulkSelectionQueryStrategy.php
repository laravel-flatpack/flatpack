<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

enum BulkSelectionQueryStrategy
{
    case Delete;
    case Restore;
    case ForceDelete;
}
