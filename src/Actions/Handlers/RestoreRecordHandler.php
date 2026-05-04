<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class RestoreRecordHandler extends ActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'restore',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(ActionContext $context): mixed
    {
        $model = $this->resolveModel($context);
        if (! $this->modelExists($model) || ! method_exists($model, 'restore')) {
            return null;
        }

        $model->restore();

        return redirect()->route('flatpack.entities.index', [
            'entity' => $context->entity,
        ]);
    }
}
