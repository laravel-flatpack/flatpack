import { createElement } from 'react';
import { CardWidget } from '@/components/widgets/card';
import { ChartWidget } from '@/components/widgets/chart-area-interactive';
import { MetricWidget } from '@/components/widgets/metric';
import { StatusWidget } from '@/components/widgets/status';
import type { FlatpackWidget } from '@/types/widgets-composition';

export function widgetGridSpanClass(widget: FlatpackWidget): string {
    switch (widget.type) {
        case 'chart':
            return 'col-span-1 md:col-span-2 xl:col-span-4';
        default:
            return 'col-span-1';
    }
}

export function renderWidgetByType(widget: FlatpackWidget): React.ReactNode {
    switch (widget.type) {
        case 'metric':
            return createElement(MetricWidget, { widget });
        case 'card':
            return createElement(CardWidget, { widget });
        case 'status':
            return createElement(StatusWidget, { widget });
        case 'chart':
            return createElement(ChartWidget, { widget });
        default:
            return null;
    }
}
