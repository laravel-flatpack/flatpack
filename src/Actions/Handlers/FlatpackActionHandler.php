<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\EntityActionExecutor;
use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Runtime\ActionRuntime;
use Flatpack\Services\SaveRecord\SaveRecordService;
use Flatpack\Support\EloquentModelResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Base for record-level actions: authorization helpers, nested action dispatch, {@see resolveModel()},
 * and {@see SaveRecordService} for custom handlers that need the same save pipeline as the built-in
 * `save` action.
 * Concrete handlers implement authorize() and handle().
 *
 * Host applications may extend this class for custom entity actions and use {@see callAction()} to
 * delegate to built-in actions (for example the `save` action) without duplicating their logic.
 */
abstract class FlatpackActionHandler implements FlatpackAction
{
    public function __construct(
        protected readonly FlatpackAuthorizer $authorizer,
        protected readonly ActionRuntime $actionRuntime,
        protected readonly EntityActionExecutor $actionExecutor,
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

    protected function resolveModel(FlatpackActionContext $context): ?Model
    {
        return EloquentModelResolver::fromContext($context);
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
     * execution with {@see EntityActionExecutor} (authorization passthrough, DB errors as validation).
     */
    protected function callAction(string $actionName, FlatpackActionContext $context): mixed
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

        $nestedContext = new FlatpackActionContext(
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
