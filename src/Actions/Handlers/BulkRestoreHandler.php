<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Services\Lists\BulkRestoreService;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkRestoreHandler extends FlatpackBulkActionHandler
{
    public function __construct(
        private readonly BulkRestoreService $bulkRestoreService,
    ) {}

    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'restore',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackBulkActionContext $context): int
    {
        return $this->bulkRestoreService->restore(
            modelClass: $context->modelClass,
            records: $context->records,
            schema: $context->schema,
            search: $context->search,
            filters: $context->filters,
        );
    }
}
