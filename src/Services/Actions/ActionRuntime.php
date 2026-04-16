<?php

declare(strict_types=1);

namespace Flatpack\Services\Actions;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ActionRuntime
{
    public function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            abort(404, sprintf('Flatpack action "%s" is not configured. Add it to config/flatpack.php under "actions".', $action));
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
            abort(404, sprintf('Flatpack bulk action "%s" is not configured. Add it to config/flatpack.php under "bulk_actions".', $action));
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
        } elseif ($exception instanceof QueryException) {
            $validationError = $this->databaseValidationError($exception);
            if ($validationError !== null) {
                return ValidationException::withMessages([
                    $validationError['field'] => $validationError['message'],
                ]);
            }
        } elseif (config('app.debug')) {
            $message = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $message;
        }

        return ValidationException::withMessages([
            'flatpack' => $message,
        ]);
    }

    /**
     * @return array{field: string, message: string}|null
     */
    private function databaseValidationError(QueryException $exception): ?array
    {
        $message = $exception->getMessage();

        if (preg_match('/NOT NULL constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            $field = $matches[1];

            return [
                'field' => $field,
                'message' => sprintf('%s is required.', str_replace('_', ' ', ucfirst($field))),
            ];
        }

        if (preg_match('/UNIQUE constraint failed: [^.]+\.([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            $field = $matches[1];

            return [
                'field' => $field,
                'message' => sprintf('%s must be unique.', str_replace('_', ' ', ucfirst($field))),
            ];
        }

        if (preg_match('/Duplicate entry .* for key .*\.([a-zA-Z0-9_]+)\'?/', $message, $matches) === 1) {
            $field = $matches[1];

            return [
                'field' => $field,
                'message' => sprintf('%s must be unique.', str_replace('_', ' ', ucfirst($field))),
            ];
        }

        return null;
    }
}
