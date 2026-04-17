<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for record-level actions: provides authorizer() and modelExists().
 * Concrete handlers implement authorize() and handle().
 */
abstract class FlatpackActionHandler implements FlatpackAction
{
    protected function authorizer(): FlatpackAuthorizer
    {
        return app(FlatpackAuthorizer::class);
    }

    protected function modelExists(?Model $model): bool
    {
        return $model instanceof Model && $model->exists;
    }
}
