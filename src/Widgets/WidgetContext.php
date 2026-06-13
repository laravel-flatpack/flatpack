<?php

declare(strict_types=1);

namespace Flatpack\Widgets;

use Illuminate\Http\Request;

final readonly class WidgetContext
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        public Request $request,
        public string $entity,
        public string $widgetId,
        public array $definition,
    ) {}
}
