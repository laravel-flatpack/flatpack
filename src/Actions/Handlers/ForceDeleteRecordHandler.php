<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class ForceDeleteRecordHandler extends ActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'forceDelete',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(ActionContext $context): mixed
    {
        $model = $this->resolveModel($context);
        if (! $this->modelExists($model)) {
            return null;
        }

        $model->forceDelete();

        return redirect()->route('flatpack.entities.index', [
            'entity' => $context->entity,
        ]);
    }
}
