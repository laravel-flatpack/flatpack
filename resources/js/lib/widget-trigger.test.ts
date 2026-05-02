import { describe, expect, it } from 'vitest';
import { widgetGridSpanClass } from '@/lib/widget-trigger';
import type {
    FlatpackMetricWidget,
    FlatpackWidget,
} from '@/types/widgets-composition';

describe('widgetGridSpanClass', () => {
    it('uses YAML span when present', () => {
        const w = {
            type: 'metric',
            provider: 'p',
            label: 'M',
            value_format: { kind: 'number' },
            period: { kind: 'custom' },
            span: 'half',
        } satisfies FlatpackMetricWidget;
        expect(widgetGridSpanClass(w)).toContain('xl:col-span-2');
    });

    it('falls back to type-based defaults when span omitted', () => {
        const chart = {
            type: 'chart',
            provider: 'p',
            label: 'C',
            chart: {
                variant: 'area',
                series: [{ key: 'a', label: 'A' }],
            },
        } as FlatpackWidget;
        expect(widgetGridSpanClass(chart)).toContain('xl:col-span-4');

        const metric = {
            type: 'metric',
            provider: 'p',
            label: 'M',
            value_format: { kind: 'number' },
            period: { kind: 'custom' },
        } satisfies FlatpackMetricWidget;
        expect(widgetGridSpanClass(metric)).toBe('col-span-1');
    });
});
