<?php

declare(strict_types=1);

namespace Flatpack\Widgets;

use Flatpack\Contracts\Widgets\WidgetDataProvider;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class FlatpackCardWidget implements WidgetDataProvider
{
    final public function authorize(Authenticatable $user, WidgetDataContext $context): bool
    {
        return true;
    }

    /**
     * Standard card widget payload.
     *
     * @return array{
     *     status: 'default'|'error'|'info'|'success'|'warning',
     *     value: float|int|string,
     *     context: string,
     *     updated_at: string,
     *     description: string
     * }
     */
    final public function handle(WidgetDataContext $context): array
    {
        return [
            'status' => $this->resolveStatus($context),
            'value' => $this->resolveValue($context) ?? 0,
            'context' => trim($this->resolveContext($context)),
            'updated_at' => trim($this->resolveUpdatedAt($context)),
            'description' => trim($this->resolveDescription($context)),
        ];
    }

    /**
     * @return 'default'|'error'|'info'|'success'|'warning'
     */
    protected function resolveStatus(WidgetDataContext $context): string
    {
        return 'default';
    }

    protected function resolveValue(WidgetDataContext $context): float|int|string|null
    {
        return null;
    }

    protected function resolveContext(WidgetDataContext $context): string
    {
        return '';
    }

    protected function resolveUpdatedAt(WidgetDataContext $context): string
    {
        return '';
    }

    protected function resolveDescription(WidgetDataContext $context): string
    {
        return '';
    }
}
