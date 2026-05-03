import { createElement } from 'react';
import { CardWidget } from '@/components/widgets/card';
import { ChartWidget } from '@/components/widgets/chart';
import { GridWidget } from '@/components/widgets/grid-widget';
import { MetricWidget } from '@/components/widgets/metric';
import { StatusWidget } from '@/components/widgets/status';
import { TableWidget } from '@/components/widgets/table-widget';
import { canonicalizeSpan, spanClassFor } from '@/lib/field-span';
import type { FlatpackWidget } from '@/types/widgets-composition';

export function widgetGridSpanClass(widget: FlatpackWidget): string {
    const resolved = canonicalizeSpan(widget.span);
    if (resolved !== undefined) {
        return spanClassFor(resolved, 'dashboard') ?? 'col-span-1';
    }

    switch (widget.type) {
        case 'chart':
        case 'table':
        case 'grid':
            return 'col-span-1 md:col-span-2 xl:col-span-4';
        default:
            return 'col-span-1';
    }
}

export function renderWidgetByType(
    widgetId: string,
    widget: FlatpackWidget,
): React.ReactNode {
    switch (widget.type) {
        case 'metric':
            return createElement(MetricWidget, { widget });
        case 'card':
            return createElement(CardWidget, { widget });
        case 'status':
            return createElement(StatusWidget, { widget });
        case 'chart':
            return createElement(ChartWidget, { widget });
        case 'table':
            return createElement(TableWidget, { widgetId, widget });
        case 'grid':
            return createElement(GridWidget, { widgetId, widget });
        default:
            return null;
    }
}
