<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Http\Request;

final readonly class FlatpackActionContext
{
    /**
     * @param  array<string, mixed>  $composition
     */
    public function __construct(
        public Request $request,
        public string $entity,
        public string $compositionType,
        public array $composition,
        public ?object $model = null,
    ) {}
}
