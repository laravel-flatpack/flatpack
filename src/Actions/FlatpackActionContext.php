<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Http\Request;

final readonly class FlatpackActionContext
{
    /**
     * @param  array<string, mixed>  $composition
     * @param  array<string, mixed>|null  $schema
     */
    public function __construct(
        public Request $request,
        public string $entity,
        public string $actionName,
        public string $modelClass,
        public ?string $record,
        public string $compositionType,
        public array $composition,
        public ?array $schema = null,
        public ?object $model = null,
    ) {}

    /**
     * @param  array<string, mixed>  $composition
     */
    public static function fromComposition(
        Request $request,
        string $entity,
        string $actionName,
        string $compositionType,
        array $composition,
        ?object $model = null,
    ): self {
        $schema = isset($composition['schema']) && is_array($composition['schema'])
            ? $composition['schema']
            : null;
        $record = $request->route('record');

        return new self(
            request: $request,
            entity: $entity,
            actionName: $actionName,
            modelClass: isset($composition['model'])
                ? trim((string) $composition['model'])
                : '',
            record: is_string($record) && trim($record) !== ''
                ? trim($record)
                : null,
            compositionType: $compositionType,
            composition: $composition,
            schema: $schema,
            model: $model,
        );
    }
}
