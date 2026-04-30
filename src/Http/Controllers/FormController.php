<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Composition\FormComposition;
use Flatpack\Http\Controllers\Concerns\AuthorizesModelAbility;
use Flatpack\Http\Controllers\Concerns\BuildsFormPageProps;
use Flatpack\Http\Controllers\Concerns\DispatchesActions;
use Flatpack\Http\Controllers\Concerns\LoadsFormComposition;
use Flatpack\Http\Controllers\Concerns\NormalizesFormSchema;
use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\SuccessRedirect;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Throwable;

final readonly class FormController
{
    use AuthorizesModelAbility;
    use BuildsFormPageProps;
    use DispatchesActions;
    use LoadsFormComposition;
    use NormalizesFormSchema;
    use ResolvesWidgets;

    public function __construct(
        private WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private WidgetRuntime $widgetRuntime,
        private ListRecordsLoader $listRecordsLoader,
    ) {}

    /**
     * Display the create form for a new record.
     */
    public function create(Request $request, string $entity): Response|JsonResponse
    {
        $form = $this->loadForm($entity);
        $schema = $this->loadSchema($entity);
        $modelClass = $this->formModelClass($form);
        $this->ensureModelAbility($request, $modelClass, 'create');

        $normalized = $this->normalizeSchemaForFormPage(
            entity: $entity,
            schema: $schema,
            modelClass: $modelClass,
        );
        $widgetsSchema = $this->normalizedWidgetsSchema($normalized->schema, $normalized->debugLog);
        $resolvedWidgets = $this->resolveWidgetDataWhenPresent(
            $request,
            $entity,
            $widgetsSchema['widgets'] ?? [],
            $normalized->debugLog,
        );

        return FlatpackResponse::inertia(
            view: 'form',
            data: $this->formPageProps(
                $entity,
                $form,
                $normalized->schema,
                'create',
                null,
                [],
                $normalized->debugLog,
                $resolvedWidgets,
                $widgetsSchema,
            ),
            compositionDebugLog: $normalized->debugLog,
        );
    }

    /**
     * Display the edit form for an existing record.
     */
    public function edit(Request $request, string $entity, string $record): Response|JsonResponse
    {
        $form = $this->loadForm($entity);
        $schema = $this->loadSchema($entity);
        $modelClass = $this->formModelClass($form);

        if (! $this->hasRenderableFields($schema)) {
            $debugContext = FlatpackResponse::compositionDebugContextForEntity($entity, 'form.yaml');
            $debugLog = FlatpackResponse::compositionDebugLog($debugContext);

            return FlatpackResponse::inertia(
                view: 'form',
                data: $this->formPageProps($entity, $form, $schema, 'edit', $record, [], $debugLog),
                compositionDebugLog: $debugLog,
            );
        }
        $model = $this->resolveOptionalRecordModel($modelClass, $record);

        if (! $model instanceof Model) {
            return $this->recordNotFoundResponse($entity, $form);
        }
        $this->ensureModelAbility($request, $modelClass, 'view', $model);

        $normalized = $this->normalizeSchemaForFormPage(
            entity: $entity,
            schema: $schema,
            modelClass: $modelClass,
            model: $model,
        );
        $values = $this->formSchemaNormalizer()->formValuesFromModel(
            $model,
            $normalized->schema,
            $normalized->debugLog,
        );
        $widgetsSchema = $this->normalizedWidgetsSchema($normalized->schema, $normalized->debugLog);
        $resolvedWidgets = $this->resolveWidgetDataWhenPresent(
            $request,
            $entity,
            $widgetsSchema['widgets'] ?? [],
            $normalized->debugLog,
        );

        return FlatpackResponse::inertia(
            view: 'form',
            data: $this->formPageProps(
                $entity,
                $form,
                $normalized->schema,
                'edit',
                $record,
                $values,
                $normalized->debugLog,
                $resolvedWidgets,
                $widgetsSchema,
            ),
            compositionDebugLog: $normalized->debugLog,
        );
    }

    /**
     * Persist create or update from the form submission (single POST endpoint).
     *
     * Uses HTTP 303 redirects after successful POST so the browser replaces the
     * POST URL in history (see PRG pattern). Handler-provided redirects keep
     * their 303 status as well.
     *
     * @throws AuthorizationException When the action handler denies access.
     * @throws ValidationException When the submit fails in a user-recoverable way.
     * @throws Throwable
     */
    public function submit(FormSubmitRequest $request, string $entity): RedirectResponse
    {
        $form = $this->loadForm($entity);
        $schema = $this->loadSchema($entity);
        $modelClass = $this->formModelClass($form);
        $record = self::recordKeyFromSubmitRequest($request);
        $actionName = trim((string) $request->validated('action'));
        $model = $this->resolveSubmitModel($modelClass, $record);
        $handler = $this->resolveRecordActionHandlerOrFail($actionName);

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $this->actionRuntime()->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);

        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: $actionName,
                modelClass: $modelClass,
                record: $record,
                compositionType: 'form',
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            if ($exception instanceof AuthorizationException) {
                throw $exception;
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);

            throw $this->actionRuntime()->toUserFacingValidationException($exception);
        }

        if ($result instanceof RedirectResponse) {
            return $result->setStatusCode(303);
        }

        $savedModel = $result instanceof Model ? $result : $model;
        if (! $savedModel instanceof Model || $savedModel->getKey() === null) {
            return back(303)->with('flatpack', [
                'save' => true,
            ]);
        }

        $savedKey = (string) $savedModel->getKey();
        $formActionId = $request->input('form_action_id');
        $target = SuccessRedirect::successRedirectForFormSubmit(
            $schema,
            is_string($formActionId) ? $formActionId : null,
            $actionName,
        );
        if ($target === null) {
            return redirect()->route('flatpack.entities.edit', [
                'entity' => $entity,
                'record' => $savedKey,
            ])->setStatusCode(303);
        }

        return SuccessRedirect::responseForFormSave(
            $target,
            $entity,
            $record === null,
            $savedKey,
        );
    }

    private function recordNotFoundResponse(string $entity, FormComposition $form): Response|JsonResponse
    {
        return FlatpackResponse::inertia('errors/record-not-found', [
            'entity' => $entity,
            'entityName' => mb_strtolower($form->name ?? $entity),
        ]);
    }

    private function widgetSchemaNormalizer(): WidgetSchemaNormalizer
    {
        return $this->widgetSchemaNormalizer;
    }

    private function widgetRuntime(): WidgetRuntime
    {
        return $this->widgetRuntime;
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return $this->listRecordsLoader;
    }
}
