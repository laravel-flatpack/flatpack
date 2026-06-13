<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
        $model = $this->resolveModel($context, mustExist: true);

        if (! method_exists($model, 'trashed') || ! method_exists($model, 'restore')) {
            throw new InvalidArgumentException('Model does not use soft deletes.');
        }
        if ($model->trashed() !== true) {
            throw new InvalidArgumentException('Record is not soft deleted.');
        }

        $model->restore();

        return redirect()->route('flatpack.entities.index', [
            'entity' => $context->entity,
        ]);
    }
}
