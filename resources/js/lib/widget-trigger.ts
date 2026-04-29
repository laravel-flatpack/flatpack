import { createElement } from 'react';
import { CardWidget } from '@/components/widgets/card';
import { MetricWidget } from '@/components/widgets/metric';
import type { FlatpackWidget } from '@/types/widgets-composition';

export function widgetGridSpanClass(widget: FlatpackWidget): string {
    switch (widget.type) {
        case 'metric':
            return 'col-span-1';
        case 'card':
            return 'col-span-1 md:col-span-2';
        default:
            return '';
    }
}

export function renderWidgetByType(widget: FlatpackWidget): React.ReactNode {
    switch (widget.type) {
        case 'metric':
            return createElement(MetricWidget, { widget });
        case 'card':
            return createElement(CardWidget, { widget });
        default:
            return null;
    }
}
