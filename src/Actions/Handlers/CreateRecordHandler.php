<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordHandler extends ActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'create',
            modelClass: $modelClass,
        );
    }

    public function handle(ActionContext $context): mixed
    {
        return redirect()->route('flatpack.entities.create', [
            'entity' => $context->entity,
        ]);
    }
}
