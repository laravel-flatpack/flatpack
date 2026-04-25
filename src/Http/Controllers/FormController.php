<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Actions\FlatpackActionContext;
use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormComposition;
use Flatpack\Http\FlatpackResponse;
use Flatpack\Http\Requests\FormSubmitRequest;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\HeaderActions;
use Flatpack\Support\ActionRuntime;
use Flatpack\Support\SuccessRedirect;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Throwable;

final readonly class FormController
{
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
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);

        return FlatpackResponse::inertia(
            'form',
            $this->formPageProps($entity, $form, $schema, 'create', null, []),
            $request->boolean('json'),
        );
    }

    /**
     * Display the edit form for an existing record.
     */
    public function edit(Request $request, string $entity, string $record): Response|JsonResponse
    {
        $showJsonResponse = $request->boolean('json');
        $schema = $this->entityComposition->formSchema($entity);
        $form = $this->entityComposition->formFor($entity);
        $model = $this->actions->resolveOptionalRecordModel(
            (string) ($form->model ?? ''),
            $record,
        );

        if (is_null($model)) {
            return FlatpackResponse::inertia('errors/record-not-found', [
                'entity' => $entity,
                'entityName' => mb_strtolower($form->name ?? $entity),
            ], $showJsonResponse);
        }

        $debugLog = FlatpackResponse::compositionDebugLog($entity . '/form.yaml');
        $formModel = (string) ($form->model ?? '');
        $normalizedSchema = $this->formSchemaNormalizer->normalizedFormSchema(
            $schema,
            $debugLog,
            $formModel !== '' ? $formModel : null,
            $model,
        );
        $values = $this->formSchemaNormalizer->formValuesFromModel($model, $normalizedSchema, $debugLog);

        return FlatpackResponse::inertia(
            'form',
            array_merge($this->formPageProps(
                $entity,
                $form,
                $normalizedSchema,
                'edit',
                $record,
                $values,
            ), [
                'composition_debug_log' => $debugLog,
                '_flatpack_skip_form_schema_normalize' => true,
            ]),
            $showJsonResponse,
        );
    }

    /**
     * Persist create or update from the form submission.
     *
     * Uses HTTP 303 redirects after successful POST so the browser replaces the
     * POST URL in history (see PRG pattern). Handler-provided redirects keep
     * their 303 status as well.
     *
     * @throws AuthorizationException When the action handler denies access.
     * @throws ValidationException When the save fails in a user-recoverable way.
     * @throws Throwable
     */
    public function save(FormSubmitRequest $request, string $entity, ?string $record = null): RedirectResponse
    {
        $form = $this->entityComposition->formFor($entity);
        $schema = $this->entityComposition->formSchema($entity);
        $modelClass = (string) ($form->model ?? '');
        $model = $record !== null
            ? $this->actions->resolveRecordModel($modelClass, $record, 'form')
            : null;
        $handler = $this->actions->resolveRecordActionHandler('save');
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $this->actions->ensureRecordActionAuthorized($handler, $user, $modelClass, $model);

        try {
            $result = $handler->handle(new FlatpackActionContext(
                request: $request,
                entity: $entity,
                actionName: 'save',
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

            if (config('app.debug') || (bool) config('flatpack.log_form_save_failures', false)) {
                Log::warning('Flatpack form save failed (see ActionRuntime::toUserFacingValidationException for user message)', [
                    'entity' => $entity,
                    'record' => $record,
                    'form_action_id' => $request->input('form_action_id'),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
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
        $target = SuccessRedirect::successRedirectForFormSave(
            $schema,
            is_string($formActionId) ? $formActionId : null,
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
}
