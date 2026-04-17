<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class DeleteRecordHandler extends FlatpackActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->authorizer()->authorizeModelAbility(
            user: $user,
            ability: 'delete',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $context->model;
        if (! $model instanceof Model) {
            return null;
        }

        return $model->delete();
    }
}
