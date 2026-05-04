<?php

declare(strict_types=1);

namespace Flatpack\Services\SaveRecord;

use Flatpack\Actions\ActionContext;
use Flatpack\Actions\RelationFormSynchronizer;
use Flatpack\Support\EloquentModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

final readonly class SaveRecordService
{
    public function __construct(
        private ResolveFormPayload $resolveFormPayload,
        private DeferredRelationValidationService $deferredRelationValidationService,
        private RelationSyncErrorMapper $relationSyncErrorMapper,
        private RelationFormSynchronizer $relationFormSynchronizer,
    ) {}

    public function resolveModel(ActionContext $context): ?Model
    {
        return EloquentModelResolver::fromContext($context);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolvePayload(ActionContext $context): ?array
    {
        return $this->resolveFormPayload->resolve($context->request);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function validate(
        Model $model,
        array $values,
        ActionContext $context,
    ): WritablePayloadResult {
        return $this->resolveFormPayload->validate(
            model: $model,
            compositionType: $context->compositionType,
            schema: $context->schema,
            values: $values,
        );
    }

    public function save(
        Model $model,
        WritablePayloadResult $validated,
    ): Model {
        if ($validated->attributes === [] && ! $validated->hasDeferredRelationPayload) {
            return $model;
        }

        if ($validated->attributes === [] && $validated->hasDeferredRelationPayload && $model->getKey() === null) {
            $model->save();

            return $model->fresh() ?? $model;
        }

        if ($validated->attributes !== []) {
            $model->fill($validated->attributes);
            $model->save();
        }

        return $validated->attributes !== [] ? ($model->fresh() ?? $model) : $model;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function validateRelations(
        Model $model,
        array $values,
        ActionContext $context,
    ): ValidatedRelationsPayload {
        if (! $this->shouldProcessRelations($model, $context)) {
            return new ValidatedRelationsPayload(
                schema: $context->schema,
                values: $values,
                shouldSync: false,
            );
        }

        $rowValidationErrors = $this->deferredRelationValidationService->requiredRowErrors(
            schema: $context->schema,
            values: $values,
        );
        if ($rowValidationErrors !== []) {
            throw ValidationException::withMessages($rowValidationErrors);
        }

        return new ValidatedRelationsPayload(
            schema: $context->schema,
            values: $values,
            shouldSync: true,
        );
    }

    public function saveRelations(
        Model $model,
        ValidatedRelationsPayload $validated,
    ): Model {
        if (! $validated->shouldSync) {
            return $model;
        }

        try {
            $this->relationFormSynchronizer->sync(
                $model,
                $validated->schema,
                $validated->values,
            );
        } catch (QueryException $exception) {
            $mapped = $this->relationSyncErrorMapper->mapRequiredConstraint(
                exception: $exception,
                schema: $validated->schema,
                values: $validated->values,
            );
            if ($mapped !== null) {
                throw ValidationException::withMessages([
                    $mapped['field'] => $mapped['message'],
                ]);
            }

            throw $exception;
        }

        return $model->fresh() ?? $model;
    }

    private function shouldProcessRelations(Model $model, ActionContext $context): bool
    {
        return $context->compositionType === 'form'
            && $model->getKey() !== null;
    }
}
