import { describe, expect, it } from 'vitest';
import {
    formatMetricValue,
    formatTrendPercent,
    metricTrendComment,
} from '@/lib/widget';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

function metricWidget(overrides: Partial<FlatpackMetricWidget> = {}): FlatpackMetricWidget {
    return {
        type: 'metric',
        provider: 'total_revenue',
        label: 'Total Revenue',
        description: 'Revenue trend for the last 6 months',
        value_format: {
            kind: 'currency',
            currency: 'USD',
            maximumFractionDigits: 2,
        },
        period: {
            kind: 'month',
            label: 'this month',
        },
        trend: {
            precision: 1,
        },
        data: {
            value: 1250,
            trend: {
                direction: 'up',
                percent: 12.5,
                comment: 'Trending up this month',
            },
        },
        ...overrides,
    };
}

describe('metric-widget', () => {
    it('formats currency value from resolved data', () => {
        expect(formatMetricValue(metricWidget())).toContain('1,250');
    });

    it('formats trend percent with sign', () => {
        expect(formatTrendPercent(metricWidget())).toBe('+12.5%');
    });

    it('uses provided trend comment first', () => {
        expect(metricTrendComment(metricWidget())).toBe('Trending up this month');
    });

    it('builds fallback comment when comment is missing', () => {
        const widget = metricWidget({
            data: {
                value: 1250,
                trend: {
                    direction: 'down',
                    percent: -20,
                },
            },
        });

        expect(metricTrendComment(widget)).toBe('Down 20% this month');
    });
});
