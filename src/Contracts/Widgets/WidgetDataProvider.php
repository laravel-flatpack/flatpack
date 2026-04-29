<?php

declare(strict_types=1);

namespace Flatpack\Contracts\Widgets;

use Flatpack\Widgets\WidgetDataContext;
use Illuminate\Contracts\Auth\Authenticatable;

interface WidgetDataProvider
{
    /**
     * Whether the user may resolve this widget's data.
     */
    public function authorize(Authenticatable $user, WidgetDataContext $context): bool;

    /**
     * Returns resolved widget data payload for frontend rendering.
     *
     * @return array<string, mixed>
     */
    public function handle(WidgetDataContext $context): array;
}
