<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Actions\EntityActionExecutor;
use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Shared Flatpack record/bulk action runtime for form submit and list/entity action controllers.
 */
trait DispatchesActions
{
    /**
     * Non-empty {@code record} request value means edit; omitted or blank means create.
     */
    private static function recordKeyFromSubmitRequest(FormSubmitRequest $request): ?string
    {
        $raw = $request->input('record');
        if (! is_string($raw)) {
            return null;
        }
        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolveOptionalRecordModel(string $modelClass, string $record): ?Model
    {
        return $this->actionRuntime()->resolveOptionalRecordModel($modelClass, $record);
    }

    private function resolveSubmitModel(string $modelClass, ?string $record): ?Model
    {
        if ($record === null) {
            return null;
        }

        try {
            return $this->actionRuntime()->resolveRecordModel($modelClass, $record, 'form');
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
    }

    /**
     * @throws ValidationException
     */
    private function resolveRecordActionHandlerOrFail(string $actionName): FlatpackAction
    {
        try {
            return $this->actionRuntime()->resolveRecordActionHandler($actionName);
        } catch (ActionRuntimeException $exception) {
            throw ValidationException::withMessages([
                'action' => [$exception->getMessage()],
            ]);
        }
    }

    private function requireUserOrAbort(Request $request): Authenticatable
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        return $user;
    }

    private function resolveRecordActionHandlerOrAbort(string $action): FlatpackAction
    {
        try {
            return $this->actionRuntime()->resolveRecordActionHandler($action);
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
    }

    private function resolveBulkActionHandlerOrAbort(string $action): FlatpackBulkAction
    {
        try {
            return $this->actionRuntime()->resolveBulkActionHandler($action);
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
    }

    private function resolveListRecordModelOrAbort(
        string $modelClass,
        string $record,
        bool $includeTrashed = false,
    ): Model {
        try {
            if ($includeTrashed) {
                return $this->actionRuntime()->resolveRecordModelWithTrashed($modelClass, $record, 'list');
            }

            return $this->actionRuntime()->resolveRecordModel($modelClass, $record, 'list');
        } catch (ActionRuntimeException $exception) {
            abort($exception->statusCode(), $exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function executeRecordActionContext(
        FlatpackAction $handler,
        Request $request,
        string $entity,
        string $actionName,
        string $modelClass,
        ?string $record,
        ?array $schema,
        ?Model $model,
    ): mixed {
        return $this->actionExecutor()->execute(fn () => $handler->handle(
            new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: $actionName,
                modelClass: $modelClass,
                record: $record,
                compositionType: 'list',
                schema: $schema,
                model: $model,
            )
        ));
    }

    private function actionRuntime(): ActionRuntime
    {
        return app(ActionRuntime::class);
    }

    private function actionExecutor(): EntityActionExecutor
    {
        return app(EntityActionExecutor::class);
    }
}
