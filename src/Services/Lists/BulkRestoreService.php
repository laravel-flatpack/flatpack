<?php

declare(strict_types=1);

namespace Flatpack\Services\Lists;

use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final readonly class BulkRestoreService
{
    public function __construct(
        private FlatpackAuthorizer $authorizer,
        private BulkQueryBuilder $bulkQueryBuilder,
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  'all'|list<string|int>  $records
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $filters
     */
    public function restore(
        string $modelClass,
        array|string $records,
        ?array $schema,
        string $search = '',
        array $filters = [],
    ): int {
        $this->bulkQueryBuilder->assertValidEloquentModel(
            $modelClass,
            'Invalid model class for bulk restore.',
            'Bulk restore model must extend Eloquent Model.',
        );

        $selection = $this->bulkQueryBuilder->beginSelectionQuery(
            $modelClass,
            BulkSelectionQueryStrategy::Restore,
        );
        if (! method_exists($selection->model, 'restore')) {
            return 0;
        }
        if (! $this->bulkQueryBuilder->constrainToSelection(
            $selection,
            $records,
            $schema,
            $search,
            $filters,
        )) {
            return 0;
        }

        $user = Auth::user();
        if ($user === null) {
            return 0;
        }

        $authorizedRecords = [];
        foreach ($selection->query->get() as $record) {
            if (! $record instanceof Model) {
                continue;
            }
            if (! method_exists($record, 'trashed') || $record->trashed() !== true) {
                continue;
            }
            if ($this->authorizer->allows($user, 'restore', $modelClass, $record)) {
                $authorizedRecords[] = $record;
            }
        }
        if ($authorizedRecords === []) {
            return 0;
        }

        $restored = 0;
        foreach ($authorizedRecords as $record) {
            if (! method_exists($record, 'restore')) {
                continue;
            }
            if ($record->restore()) {
                $restored++;
            }
        }

        return $restored;
    }
}
