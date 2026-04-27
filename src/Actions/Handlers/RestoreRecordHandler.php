<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class RestoreRecordHandler extends FlatpackActionHandler
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

    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $context->model;
        if (! $model instanceof Model || ! method_exists($model, 'restore')) {
            return null;
        }

        $model->restore();

        return redirect()->route('flatpack.entities.index', [
            'entity' => $context->entity,
        ]);
    }
}
