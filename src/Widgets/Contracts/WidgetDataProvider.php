<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Contracts;

use Flatpack\Widgets\WidgetContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;

interface WidgetDataProvider
{
    /**
     * Whether the user may resolve this widget's data.
     */
    public function authorize(Authenticatable $user, WidgetContext $context): bool;

    /**
     * Returns resolved widget data payload for frontend rendering.
     */
    public function handle(WidgetContext $context): Arrayable|array;
}
