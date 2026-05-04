<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Http\Request;

final readonly class ActionContext
{
    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function __construct(
        public Request $request,
        public string $entity,
        public string $actionName,
        public string $modelClass,
        public ?string $record,
        public string $compositionType,
        public ?string $scope = null,
        public ?array $schema = null,
        public ?object $model = null,
    ) {}
}
