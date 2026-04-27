<?php

declare(strict_types=1);

namespace Flatpack\Actions\Handlers;

use Flatpack\Actions\FlatpackBulkActionContext;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Flatpack\Services\Lists\BulkDeleteService;
use Illuminate\Contracts\Auth\Authenticatable;

final class BulkDeleteHandler extends FlatpackBulkActionHandler
{
    public function __construct(
        FlatpackAuthorizer $authorizer,
        private readonly BulkDeleteService $bulkDeleteService,
    ) {
        parent::__construct($authorizer);
    }

    public function authorize(Authenticatable $user, string $modelClass): bool
    {
        return $this->canPerformAction(
            user: $user,
            ability: 'delete',
            modelClass: $modelClass
        );
    }

    public function handle(FlatpackBulkActionContext $context): int
    {
        return $this->bulkDeleteService->delete(
            modelClass: $context->modelClass,
            records: $context->records,
            schema: $context->schema,
            user: $context->user,
            search: $context->search,
            filters: $context->filters,
        );
    }
}
