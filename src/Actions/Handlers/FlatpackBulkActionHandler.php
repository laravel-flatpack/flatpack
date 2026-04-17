<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;

/**
 * Base for bulk actions: provides authorizer().
 * Concrete handlers implement authorize() and handle().
 */
abstract class FlatpackBulkActionHandler implements FlatpackBulkAction
{
    protected function authorizer(): FlatpackAuthorizer
    {
        return app(FlatpackAuthorizer::class);
    }
}
