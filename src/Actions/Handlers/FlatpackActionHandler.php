<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Support\EloquentModelResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for record-level actions: provides {@see canPerformAction()}.
 * Concrete handlers implement authorize() and handle().
 */
abstract class FlatpackActionHandler implements FlatpackAction
{
    public function __construct(
        protected readonly FlatpackAuthorizer $authorizer,
    ) {}

    protected function canPerformAction(
        Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool {
        return $this->authorizer->allows(
            user: $user,
            ability: $ability,
            modelClass: $modelClass,
            model: $model,
        );
    }

    protected function resolveModel(FlatpackActionContext $context): ?Model
    {
        return EloquentModelResolver::fromContext($context);
    }
}
