<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\SaveRecord\SaveRecordService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class SaveRecordHandler extends FlatpackActionHandler
{
    public function __construct(
        FlatpackAuthorizer $authorizer,
        private readonly SaveRecordService $saveRecordService,
    ) {
        parent::__construct($authorizer);
    }

    public function authorize(Authenticatable $user, string $modelClass, ?Model $model): bool
    {
        if ($model instanceof Model && $model->exists) {
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
    public function handle(FlatpackActionContext $context): mixed
    {
        $model = $this->saveRecordService->resolveModel($context);
        if (! $model instanceof Model) {
            return null;
        }

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
