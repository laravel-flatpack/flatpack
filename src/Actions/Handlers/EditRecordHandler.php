<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Exception;
use Flatpack\Actions\FlatpackActionContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class EditRecordHandler extends FlatpackActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'update',
            modelClass: $modelClass,
            model: $model,
        );
    }

    public function handle(FlatpackActionContext $context): mixed
    {
        if ($context->model === null) {
            throw new Exception('Model not found');
        }

        return redirect()->route('flatpack.entities.edit', [
            'entity' => $context->entity,
            'record' => $context->model->getKey(),
        ]);
    }
}
