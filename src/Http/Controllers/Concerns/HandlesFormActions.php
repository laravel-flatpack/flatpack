<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Shared submit/action runtime helpers for Flatpack form controllers.
 */
trait HandlesFormActions
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

    private function actionRuntime(): ActionRuntime
    {
        return app(ActionRuntime::class);
    }
}
