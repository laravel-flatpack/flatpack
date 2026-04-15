<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Actions;

use Flatpack\Actions\FlatpackBulkActionContext;

/**
 * Dedicated contract for list-level bulk actions (e.g. bulk delete).
 *
 * Bulk handlers receive a strongly-typed context focused on list selections.
 */
interface FlatpackBulkAction
{
    public function handle(FlatpackBulkActionContext $context): int;
}
