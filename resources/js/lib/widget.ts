import { WIDGET_STATUS_VALUES } from '@/lib/generated/composition-schema-keys';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

export function formatMetricValue(widget: FlatpackMetricWidget): string {
    const value = widget.data?.value ?? 0;
    const format = widget.value_format;

    if (format.kind === 'currency') {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: format.currency ?? 'USD',
            minimumFractionDigits: format.minimumFractionDigits ?? 0,
            maximumFractionDigits: format.maximumFractionDigits ?? 2,
        }).format(value);
    }

    if (format.kind === 'percentage') {
        return new Intl.NumberFormat(undefined, {
            style: 'percent',
            minimumFractionDigits: format.minimumFractionDigits ?? 0,
            maximumFractionDigits: format.maximumFractionDigits ?? 1,
        }).format(value);
    }

    return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: format.minimumFractionDigits ?? 0,
        maximumFractionDigits: format.maximumFractionDigits ?? 2,
    }).format(value);
}

export function formatTrendPercent(widget: FlatpackMetricWidget): string {
    const percent = widget.data?.trend?.percent ?? 0;
    const sign = percent > 0 ? '+' : '';

    return `${sign}${new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: widget.trend?.precision ?? 1,
    }).format(percent)}%`;
}

export function metricTrendComment(widget: FlatpackMetricWidget): string {
    const explicit = widget.data?.trend?.comment;
    if (explicit && explicit.trim() !== '') {
        return explicit;
    }

    const direction = widget.data?.trend?.direction ?? 'flat';
    const percent = Math.abs(widget.data?.trend?.percent ?? 0);
    const periodLabel =
        widget.period.label?.trim() || `this ${widget.period.kind}`;

    if (direction === 'up') {
        return `Up ${percent}% ${periodLabel}`;
    }
    if (direction === 'down') {
        return `Down ${percent}% ${periodLabel}`;
    }

    return `No change ${periodLabel}`;
}

export function normalizeWidgetStatus(
    raw: unknown,
): (typeof WIDGET_STATUS_VALUES)[number] {
    if (typeof raw !== 'string') {
        return 'default';
    }
    const status = raw.trim();

    return WIDGET_STATUS_VALUES.includes(
        status as (typeof WIDGET_STATUS_VALUES)[number],
    )
        ? (status as (typeof WIDGET_STATUS_VALUES)[number])
        : 'default';
}
