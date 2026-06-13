<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers\Concerns;

use Flatpack\Actions\ActionContext;
use Flatpack\Contracts\Actions\FlatpackAction;
use Flatpack\Schema\Lists\Normalization\ReorderColumnResolver;
use Flatpack\Support\Exceptions\ActionRuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

trait HandlesReorderRecord
{
    /**
     * @param  array<string, mixed>  $schema
     */
    private function resolveReorderColumn(array $schema): ?string
    {
        return ReorderColumnResolver::reorderColumnFromSchema($schema);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{schema: array<string, mixed>, modelClass: string}|JsonResponse
     */
    private function resolveReorderSchemaAndModelClass(
        ?array $schema,
        string $fallbackModelClass = '',
    ): array|JsonResponse {
        if (! is_array($schema)) {
            return response()->json(['message' => 'No list.yaml configuration found for this entity.'], 422);
        }
        $modelClass = trim((string) ($schema['model'] ?? $fallbackModelClass));
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return response()->json(['message' => 'Resolved model class is invalid.'], 422);
        }

        return [
            'schema' => $schema,
            'modelClass' => $modelClass,
        ];
    }

    private function resolveReorderTargetModelOrJson404(string $modelClass, string $record): Model|JsonResponse
    {
        /** @var Model|null $model */
        $model = $modelClass::query()->find($record);
        if ($model === null) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        return $model;
    }

    private function resolveReorderHandlerOrJsonError(): FlatpackAction|JsonResponse
    {
        try {
            return $this->actionRuntime()->resolveRecordActionHandler('reorder');
        } catch (ActionRuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->statusCode());
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function executeReorderOrJsonError(
        Request $request,
        FlatpackAction $handler,
        string $entity,
        string $record,
        string $modelClass,
        array $schema,
        Model $model,
        ?string $scope = null,
    ): Model|JsonResponse {
        try {
            $reordered = $handler->handle(new ActionContext(
                request: $request,
                entity: $entity,
                actionName: 'reorder',
                modelClass: $modelClass,
                record: $record,
                compositionType: 'list',
                scope: $scope,
                schema: $schema,
                model: $model,
            ));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);
            $normalized = $this->actionRuntime()->toUserFacingValidationException($exception);

            return response()->json([
                'message' => $normalized->getMessage(),
                'errors' => $normalized->errors(),
            ], 422);
        }

        if (! $reordered instanceof Model) {
            return response()->json(['message' => 'Reorder action failed.'], 422);
        }

        return $reordered;
    }
}
