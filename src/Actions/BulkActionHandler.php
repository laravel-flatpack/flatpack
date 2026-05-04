<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Flatpack\Contracts\Actions\FlatpackBulkAction;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Lists\BulkDeleteService;
use Flatpack\Services\Lists\BulkForceDeleteService;
use Flatpack\Services\Lists\BulkRestoreService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for list bulk actions: {@see canPerformAction()}, shared bulk list services, and
 * {@see runBulkDelete()} / {@see runBulkRestore()} / {@see runBulkForceDelete()} wired from
 * {@see BulkActionContext}.
 * Concrete handlers implement authorize() and handle().
 */
abstract class BulkActionHandler implements FlatpackBulkAction
{
    public function __construct(
        protected readonly FlatpackAuthorizer $authorizer,
        protected readonly BulkDeleteService $bulkDeleteService,
        protected readonly BulkRestoreService $bulkRestoreService,
        protected readonly BulkForceDeleteService $bulkForceDeleteService,
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
     * Selection + list context passed to {@see BulkDeleteService}, {@see BulkRestoreService},
     * and {@see BulkForceDeleteService}.
     *
     * @return array{
     *     modelClass: class-string<Model>,
     *     records: 'all'|list<string|int>,
     *     schema: array<string, mixed>|null,
     *     user: Authenticatable,
     *     search: string,
     *     filters: array<string, mixed>,
     *     scope: string
     * }
     */
    protected function bulkListSelectionPayload(BulkActionContext $context): array
    {
        return [
            'modelClass' => $context->modelClass,
            'records' => $context->records,
            'schema' => $context->schema,
            'user' => $context->user,
            'search' => $context->search,
            'filters' => $context->filters,
            'scope' => $context->scope,
        ];
    }

    protected function runBulkDelete(BulkActionContext $context): int
    {
        $payload = $this->bulkListSelectionPayload($context);

        return $this->bulkDeleteService->delete(
            modelClass: $payload['modelClass'],
            records: $payload['records'],
            schema: $payload['schema'],
            user: $payload['user'],
            search: $payload['search'],
            filters: $payload['filters'],
            scope: $payload['scope'],
        );
    }

    protected function runBulkRestore(BulkActionContext $context): int
    {
        $payload = $this->bulkListSelectionPayload($context);

        return $this->bulkRestoreService->restore(
            modelClass: $payload['modelClass'],
            records: $payload['records'],
            schema: $payload['schema'],
            user: $payload['user'],
            search: $payload['search'],
            filters: $payload['filters'],
            scope: $payload['scope'],
        );
    }

    protected function runBulkForceDelete(BulkActionContext $context): int
    {
        $payload = $this->bulkListSelectionPayload($context);

        return $this->bulkForceDeleteService->forceDelete(
            modelClass: $payload['modelClass'],
            records: $payload['records'],
            schema: $payload['schema'],
            user: $payload['user'],
            search: $payload['search'],
            filters: $payload['filters'],
            scope: $payload['scope'],
        );
    }
}
