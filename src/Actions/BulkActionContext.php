<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

final readonly class BulkActionContext
{
    /**
     * @param  'all'|list<string|int>  $records
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public Request $request,
        public Authenticatable $user,
        public string $entity,
        public string $modelClass,
        public array|string $records,
        public ?array $schema = null,
        public string $search = '',
        public array $filters = [],
        public string $scope = '',
    ) {}

    /**
     * @param  array<string, mixed>|null  $schema
     */
    public static function fromRequest(
        Request $request,
        Authenticatable $user,
        string $entity,
        string $modelClass,
        ?array $schema = null,
        string $scope = '',
    ): self {
        $selection = $request->input('selection');
        if ($selection !== 'all') {
            $selection = is_array($selection) ? $selection : [];
        }

        $filters = $request->input('filters', []);
        $filters = is_array($filters) ? $filters : [];

        return new self(
            request: $request,
            user: $user,
            entity: $entity,
            modelClass: trim($modelClass),
            records: $selection === 'all' ? 'all' : $selection,
            schema: $schema,
            search: trim((string) $request->input('search', '')),
            filters: $filters,
            scope: $scope,
        );
    }
}
