<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Actions\ActionModelClassResolver;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ActionRuntime
{
    public function __construct(
        private ActionModelClassResolver $actionModelClassResolver,
    ) {}

    public function resolveRecordActionHandler(string $action): FlatpackAction
    {
        $handlerClass = config("flatpack.actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            throw new ActionRuntimeException(
                404,
                sprintf('Flatpack action "%s" is not configured. Add it to config/flatpack.php under "actions".', $action),
            );
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackAction) {
            throw new ActionRuntimeException(500, 'Flatpack action handler must implement FlatpackAction.');
        }

        return $handler;
    }

    public function resolveBulkActionHandler(string $action): FlatpackBulkAction
    {
        $handlerClass = config("flatpack.bulk_actions.{$action}");
        if (! is_string($handlerClass) || $handlerClass === '') {
            throw new ActionRuntimeException(
                404,
                sprintf('Flatpack bulk action "%s" is not configured. Add it to config/flatpack.php under "bulk_actions".', $action),
            );
        }

        $handler = app()->make($handlerClass);
        if (! $handler instanceof FlatpackBulkAction) {
            throw new ActionRuntimeException(500, 'Flatpack bulk action handler must implement FlatpackBulkAction.');
        }

        return $handler;
    }

    public function ensureRecordActionAuthorized(
        FlatpackAction $handler,
        Authenticatable $user,
        string $modelClass,
        ?Model $model,
    ): void {
        $resolved = $this->actionModelClassResolver->resolveForRecordAuthorize($modelClass, $model);
        if ($resolved === null) {
            throw new AuthorizationException($this->invalidModelClassMessage());
        }

        if ($handler->authorize($user, $resolved, $model)) {
            return;
        }

        throw new AuthorizationException($this->recordActionDeniedMessage($handler));
    }

    public function ensureBulkActionAuthorized(
        FlatpackBulkAction $handler,
        Authenticatable $user,
        string $modelClass,
    ): void {
        $resolved = $this->actionModelClassResolver->resolveEloquentModelClassOrNull($modelClass);
        if ($resolved === null) {
            throw new AuthorizationException($this->invalidModelClassMessage());
        }

        if ($handler->authorize($user, $resolved)) {
            return;
        }

        throw new AuthorizationException($this->recordActionDeniedMessage($handler));
    }

    public function resolveRecordModel(
        string $modelClass,
        string $record,
        string $context = 'record',
    ): Model {
        if ($modelClass === '' || ! class_exists($modelClass)) {
            throw new ActionRuntimeException(404, sprintf('Flatpack %s model is not configured.', $context));
        }
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new ActionRuntimeException(404, sprintf('Flatpack %s model class is invalid.', $context));
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

        $messages = [
            'flatpack' => $message,
        ];
        if (config('app.debug') || (bool) config('flatpack.log_form_save_failures', false)) {
            $messages['flatpack_exception'] = $exception::class;
            $messages['flatpack_exception_message'] = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : '(no message; see server log for stack trace)';
        }

        return ValidationException::withMessages($messages);
    }

    private function invalidModelClassMessage(): string
    {
        if (config('app.debug')) {
            return 'Flatpack could not resolve a valid Eloquent model class for this action.';
        }

        return 'This action is not authorized.';
    }

    private function recordActionDeniedMessage(FlatpackAction|FlatpackBulkAction $handler): string
    {
        if (config('app.debug')) {
            return sprintf('Flatpack denied authorization for handler [%s].', $handler::class);
        }

        return 'This action is not authorized.';
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
