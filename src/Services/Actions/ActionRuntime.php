<?php

declare(strict_types=1);

namespace Flatpack\Services\Actions;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ActionRuntime
{
    public function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            abort(404, 'Flatpack action handler is not configured.');
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackAction) {
            abort(500, 'Flatpack action handler must implement FlatpackAction.');
        }

        return $handler;
    }

    public function resolveBulkActionHandler(string $action): FlatpackBulkAction
    {
        $handlerClass = config("flatpack.bulk_actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            abort(404, 'Flatpack bulk action handler is not configured.');
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackBulkAction) {
            abort(500, 'Flatpack bulk action handler must implement FlatpackBulkAction.');
        }

        return $handler;
    }

    public function resolveRecordModel(
        string $modelClass,
        string $record,
        string $context = 'record',
    ): Model {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            abort(404, sprintf('Flatpack %s model is not configured.', $context));
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            abort(404, sprintf('Flatpack %s model class is invalid.', $context));
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = $model->getKeyName();

        return $modelClass::query()
            ->where($keyName, $record)
            ->firstOrFail();
    }

    public function resolveOptionalRecordModel(
        string $modelClass,
        string $record,
    ): ?Model {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            return null;
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $model = new $modelClass();
        $keyName = $model->getKeyName();

        return $modelClass::query()
            ->where($keyName, $record)
            ->first();
    }

    public function toUserFacingValidationException(
        Throwable $exception,
    ): ValidationException {
        report($exception);

        $message = 'This change could not be completed.';
        if ($exception instanceof MassAssignmentException) {
            $message = config('app.debug')
                ? $exception->getMessage()
                : 'This field is not writable for this model.';
        } elseif (config('app.debug')) {
            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $message;
        }

        return ValidationException::withMessages([
            'flatpack' => $message,
        ]);
    }
}
