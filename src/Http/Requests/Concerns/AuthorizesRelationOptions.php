<?php

declare(strict_types=1);

namespace Flatpack\Http\Requests\Concerns;

use Flatpack\Services\Forms\RelationOptionsAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthorizesRelationOptions
{
    use InteractsWithFlatpackAuthorization;

    protected function authorizeRelationOptionsForRelatedModel(
        ?Authenticatable $user,
        ?string $relatedModelClass,
    ): bool {
        if ($user === null) {
            return $this->denyFlatpackAuthorization('You must be logged in to load relation options.');
        }

        if ($relatedModelClass === null) {
            return true;
        }

        $authorizer = $this->container->make(RelationOptionsAuthorizer::class);
        if ($authorizer->canViewRelatedOptions($user, $relatedModelClass)) {
            return true;
        }

        return $this->denyFlatpackGateAuthorization($user, 'viewAny', new $relatedModelClass());
    }
}
