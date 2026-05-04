<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Services\SaveRecord\SaveRecordService;
use Flatpack\Support\EloquentModelResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Base for record-level actions: authorization helpers, nested action dispatch, {@see resolveModel()},
 * {@see modelExists()}, {@see resolveModelOrFail()}, and {@see SaveRecordService} for custom handlers
 * that need the same save pipeline as the built-in `save` action.
 * Concrete handlers implement authorize() and handle().
 *
 * Host applications may extend this class for custom entity actions and use {@see callAction()} to
 * delegate to built-in actions (for example the `save` action) without duplicating their logic.
 */
abstract class ActionHandler implements FlatpackAction
{
    public function __construct(
        protected readonly FlatpackAuthorizer $authorizer,
        protected readonly ActionRuntime $actionRuntime,
        protected readonly ActionExecutor $actionExecutor,
        protected readonly SaveRecordService $saveRecordService,
    ) {}

    protected function canPerformAction(
        Authenticatable $user,
        string $ability,
        string $modelClass,
        ?object $model = null,
    ): bool {
        return $this->authorizer->allows(
            user: $user,
            ability: $ability,
            modelClass: $modelClass,
            model: $model,
        );
    }

    /**
     * Resolves the model from the context.
     *
     * @param  bool  $mustExist  When true, require a persisted Eloquent row or throw {@see ModelNotFoundException}.
     * @return ($mustExist is true ? Model : ?Model)
     */
    protected function resolveModel(ActionContext $context, bool $mustExist = false): ?Model
    {
        $model = EloquentModelResolver::fromContext($context);

        if (! $model instanceof Model) {
            throw new InvalidArgumentException('Record model could not be resolved.');
        }

        if ($mustExist && ! $this->modelExists($model)) {
            throw new ModelNotFoundException('Record not found.');
        }

        return $model;
    }

    /**
     * @return class-string<Model>
     */
    protected function assertEloquentModelClass(string $modelClass, string $message): string
    {
        $class = EloquentModelResolver::validEloquentClassOrNull($modelClass);
        if ($class === null) {
            throw new InvalidArgumentException($message);
        }

        return $class;
    }

    protected function recordKeyFromContext(ActionContext $context): ?string
    {
        $record = $context->record;
        if (! is_string($record)) {
            return null;
        }

        $trimmed = trim($record);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Whether the given model is a persisted Eloquent row (exists in the database).
     */
    protected function modelExists(?Model $model): bool
    {
        return $model instanceof Model && $model->exists;
    }

    /**
     * Runs another configured record action with the same request, entity, schema, and model instance.
     * Resolves the handler from config, enforces its {@see FlatpackAction::authorize()} checks, and wraps
     * execution with {@see ActionExecutor} (authorization passthrough, DB errors as validation).
     */
    protected function callAction(string $actionName, ActionContext $context): mixed
    {
        $actionName = trim($actionName);
        if ($actionName === '') {
            throw new InvalidArgumentException('Flatpack nested action name must not be empty.');
        }

        $user = $context->request->user();
        if (! $user instanceof Authenticatable) {
            abort(403);
        }

        $handler = $this->actionRuntime->resolveRecordActionHandler($actionName);

        $model = $context->model instanceof Model ? $context->model : null;
        $this->actionRuntime->ensureRecordActionAuthorized(
            $handler,
            $user,
            $context->modelClass,
            $model,
        );

        $nestedContext = new ActionContext(
            request: $context->request,
            entity: $context->entity,
            actionName: $actionName,
            modelClass: $context->modelClass,
            record: $context->record,
            compositionType: $context->compositionType,
            scope: $context->scope,
            schema: $context->schema,
            model: $context->model,
        );

        return $this->actionExecutor->execute(
            static fn (): mixed => $handler->handle($nestedContext),
        );
    }
}
