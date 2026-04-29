<?php

declare(strict_types=1);

namespace Flatpack\Widgets;

use Flatpack\Contracts\Widgets\WidgetDataProvider;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class FlatpackMetricWidget implements WidgetDataProvider
{
    final public function authorize(Authenticatable $user, WidgetDataContext $context): bool
    {
        return true;
    }

    /**
     * Standard metric widget payload.
     *
     * @return array{
     *     value: float|int,
     *     trend: array{
     *         direction: 'up'|'down'|'flat',
     *         percent: float|int,
     *         comment: string
     *     },
     *     description?: string
     * }
     */
    final public function handle(WidgetDataContext $context): array
    {
        $payload = [
            'value' => $this->resolveValue($context) ?? 0,
            'trend' => [
                'direction' => $this->resolveTrendDirection($context),
                'percent' => $this->resolveTrendPercent($context) ?? 0,
                'comment' => $this->resolveTrendComment($context),
            ],
        ];

        $description = $this->resolveDescription($context);
        if ($description !== null && trim($description) !== '') {
            $payload['description'] = trim($description);
        }

        return $payload;
    }

    protected function resolveValue(WidgetDataContext $context): float|int|null
    {
        return null;
    }

    /**
     * @return 'up'|'down'|'flat'
     */
    protected function resolveTrendDirection(WidgetDataContext $context): string
    {
        return 'flat';
    }

    protected function resolveTrendPercent(WidgetDataContext $context): float|int|null
    {
        return null;
    }

    protected function resolveTrendComment(WidgetDataContext $context): string
    {
        return '';
    }

    protected function resolveDescription(WidgetDataContext $context): ?string
    {
        return null;
    }
}
