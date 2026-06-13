<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\ActionHandler;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class SaveRecordHandler extends ActionHandler
{
    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        if ($this->modelExists($model)) {
            return $this->canPerformAction(
                user: $user,
                ability: 'update',
                modelClass: $modelClass,
                model: $model,
            );
        }

        return $this->canPerformAction(
            user: $user,
            ability: 'create',
            modelClass: $modelClass,
            model: $model,
        );
    }

    /**
     * Handles the save record action.
     *
     * Resolve the model and payload, validate and persist data.
     * Validate and persist relations.
     * Return the saved model.
     */
    public function handle(ActionContext $context): mixed
    {
        $model = $this->resolveModel($context, mustExist: false);

        $resolved = $this->saveRecordService->resolvePayload($context);
        if ($resolved === null) {
            return $model;
        }

        $validated = $this->saveRecordService->validate(
            model: $model,
            values: $resolved,
            context: $context,
        );

        $validatedRelations = $this->saveRecordService->validateRelations(
            model: $model,
            values: $resolved,
            context: $context,
        );

        $saved = $this->saveRecordService->save(
            model: $model,
            validated: $validated,
        );

        return $this->saveRecordService->saveRelations(
            model: $saved,
            validated: $validatedRelations,
        );
    }
}
