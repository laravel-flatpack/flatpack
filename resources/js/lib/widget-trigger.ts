import { createElement } from 'react';
import { CardWidget } from '@/components/widgets/card';
import { ChartWidget } from '@/components/widgets/chart';
import { MetricWidget } from '@/components/widgets/metric';
import { StatusWidget } from '@/components/widgets/status';
import { TableWidget } from '@/components/widgets/table-widget';
import type { FlatpackWidget } from '@/types/widgets-composition';

export function widgetGridSpanClass(widget: FlatpackWidget): string {
    switch (widget.type) {
        case 'chart':
        case 'table':
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
        default:
            return null;
    }
}
