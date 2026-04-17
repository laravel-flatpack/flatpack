<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Base for bulk actions: provides authorizer() and canPerformAction().
 * Concrete handlers implement authorize() and handle().
 */
abstract class FlatpackBulkActionHandler implements FlatpackBulkAction
{
    protected function authorizer(): FlatpackAuthorizer
    {
        return app(FlatpackAuthorizer::class);
    }

    protected function canPerformAction(
        Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool {
        return $this->authorizer()->allows(
            user: $user,
            ability: $ability,
            modelClass: $modelClass,
            model: $model,
        );
    }
}
