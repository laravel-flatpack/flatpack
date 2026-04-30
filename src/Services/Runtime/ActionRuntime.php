<?php

declare(strict_types=1);

namespace Flatpack\Services\Runtime;

use Flatpack\Actions\ActionModelClassResolver;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ActionRuntime
{
    public function __construct(
        private ActionModelClassResolver $actionModelClassResolver,
        private ActionHandlerResolver $actionHandlerResolver,
        private DatabaseExceptionTranslator $databaseExceptionTranslator,
    ) {}

    public function resolveRecordActionHandler(string $action): FlatpackAction
    {
        return $this->actionHandlerResolver->resolveRecordActionHandler($action);
    }

    public function resolveBulkActionHandler(string $action): FlatpackBulkAction
    {
        return $this->actionHandlerResolver->resolveBulkActionHandler($action);
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

    public function resolveRecordModelWithTrashed(
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
        $query = $modelClass::query();
        if (method_exists($model, 'trashed')) {
            $query = $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query
            ->where($keyName, $record)
            ->firstOrFail();
    }

    public function toUserFacingValidationException(
        Throwable $exception,
    ): ValidationException {
        return $this->databaseExceptionTranslator->toUserFacingValidationException($exception);
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
}
