<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Actions;

use Flatpack\Actions\BulkActionContext;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Dedicated contract for list-level bulk actions (e.g. bulk delete).
 *
 * Bulk handlers receive a strongly-typed context focused on list selections.
 */
interface FlatpackBulkAction
{
    /**
     * Whether the user may run this bulk action for the entity model class (not per selected row).
     *
     * Flatpack validates $modelClass before calling this method.
     */
    public function authorize(Authenticatable $user, string $modelClass): bool;

    public function handle(BulkActionContext $context): int;
}
