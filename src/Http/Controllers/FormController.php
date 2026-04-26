<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormComposition;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Http\Controllers\Concerns\AuthorizesFlatpackModelAbility;
use Flatpack\Http\Controllers\Concerns\LoadsFlatpackFormComposition;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\FlatpackResponseOptions;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Schema\Forms\FormSchemaNormalizationResult;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\HeaderActions;
use Flatpack\Support\ActionRuntime;
use Flatpack\Support\CompositionDebugLog;
use Flatpack\Support\Exceptions\ActionRuntimeException;
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
    use AuthorizesFlatpackModelAbility;
    use LoadsFlatpackFormComposition;

    public function __construct(
        private EntityComposition $entityComposition,
        private ActionRuntime $actions,
        private FormSchemaNormalizer $formSchemaNormalizer,
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

        return $this->renderFormPage(
            entity: $entity,
            form: $form,
            schema: $normalized->schema,
            mode: 'create',
            record: null,
            values: [],
            debugLog: $normalized->debugLog,
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
        $model = $this->actions->resolveOptionalRecordModel($modelClass, $record);

        if (! $model instanceof Model) {
            return $this->recordNotFoundResponse($entity, $form);
        }
        $this->ensureModelAbility($request, $modelClass, 'view', $model);

        if (! $this->hasRenderableFields($schema)) {
            return $this->renderFormPage(
                entity: $entity,
                form: $form,
                schema: $schema,
                mode: 'edit',
                record: $record,
                values: [],
            );
        }

        $normalized = $this->normalizeSchemaForFormPage(
            entity: $entity,
            schema: $schema,
            modelClass: $modelClass,
            model: $model,
        );
        $values = $this->formSchemaNormalizer->formValuesFromModel(
            $model,
            $normalized->schema,
            $normalized->debugLog,
        );

        return $this->renderFormPage(
            entity: $entity,
            form: $form,
            schema: $normalized->schema,
            mode: 'edit',
            record: $record,
            values: $values,
            debugLog: $normalized->debugLog,
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

        // submit authorization flows through action handlers; create/edit view checks happen in GET endpoints.

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $this->actions->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);

        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: $actionName,
                modelClass: $modelClass,
                record: $record,
                compositionType: 'form',
                composition: $schema ?? [],
                schema: $schema,
                model: $model,
            ));
        } catch (Throwable $exception) {
            if ($exception instanceof AuthorizationException) {
                throw $exception;
            }

            throw $this->actions->toUserFacingValidationException($exception);
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

    private function resolveSubmitModel(string $modelClass, ?string $record): ?Model
    {
        if ($record === null) {
            return null;
        }

        try {
            return $this->actions->resolveRecordModel($modelClass, $record, 'form');
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
            return $this->actions->resolveRecordActionHandler($actionName);
        } catch (ActionRuntimeException $exception) {
            throw ValidationException::withMessages([
                'action' => [$exception->getMessage()],
            ]);
        }
    }

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

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function formPageProps(
        string $entity,
        FormComposition $form,
        ?array $schema,
        string $mode,
        ?string $record,
        array $values,
    ): array {
        return [
            'entity' => $entity,
            'name' => $form->name,
            'model' => $form->model,
            'icon' => $form->icon,
            'record' => $record,
            'mode' => $mode,
            'schema' => $schema,
            'values' => $values,
            'form_actions' => HeaderActions::fromSchema($schema),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function hasRenderableFields(?array $schema): bool
    {
        if ($schema === null || ! array_key_exists('fields', $schema)) {
            return false;
        }
        $fields = $schema['fields'];
        if (! is_array($fields)) {
            return false;
        }

        return count($fields) > 0;
    }

    private function formModelClass(FormComposition $form): string
    {
        return (string) ($form->model ?? '');
    }

    private function recordNotFoundResponse(string $entity, FormComposition $form): Response|JsonResponse
    {
        return FlatpackResponse::inertia('errors/record-not-found', [
            'entity' => $entity,
            'entityName' => mb_strtolower($form->name ?? $entity),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function normalizeSchemaForFormPage(
        string $entity,
        ?array $schema,
        string $modelClass,
        ?Model $model = null,
    ): FormSchemaNormalizationResult {
        return $this->formSchemaNormalizer->normalizeForFormPage(
            $schema,
            $entity . '/form.yaml',
            $modelClass !== '' ? $modelClass : null,
            $model,
        );
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $values
     */
    private function renderFormPage(
        string $entity,
        FormComposition $form,
        ?array $schema,
        string $mode,
        ?string $record,
        array $values,
        ?CompositionDebugLog $debugLog = null,
    ): Response|JsonResponse {
        return FlatpackResponse::inertia(
            'form',
            $this->formPageProps($entity, $form, $schema, $mode, $record, $values),
            new FlatpackResponseOptions(
                compositionDebugLog: $debugLog,
                skipFormSchemaNormalize: true,
            ),
        );
    }
}
