<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class EditRecordHandler extends FlatpackActionHandler
{
    public function __construct(FlatpackAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
    }

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
            throw new ModelNotFoundException();
        }

        return redirect()->route('flatpack.entities.edit', [
            'entity' => $context->entity,
            'record' => $context->model->getKey(),
        ]);
    }
}
