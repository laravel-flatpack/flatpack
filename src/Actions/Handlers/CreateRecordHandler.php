<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordHandler extends FlatpackActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->authorizer()->authorizeModelAbility(
            user: $user,
            ability: 'create',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        return redirect()->route('flatpack.entities.create', [
            'entity' => $context->entity,
        ]);
    }
}
