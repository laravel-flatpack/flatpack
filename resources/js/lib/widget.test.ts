import { describe, expect, it } from 'vitest';
import {
    formatAsRelativeTime,
    setRelativeTimeLocale,
} from '@/lib/relative-time';
import {
    formatMetricValue,
    formatTrendPercent,
    metricTrendComment,
} from '@/lib/widget';
import type { FlatpackMetricWidget } from '@/types/widgets-composition';

function metricWidget(
    overrides: Partial<FlatpackMetricWidget> = {},
): FlatpackMetricWidget {
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
    it('formats updated_at as relative minutes', () => {
        expect(
            formatAsRelativeTime(
                '2026-04-29 18:05:18',
                new Date(2026, 3, 29, 18, 9, 18),
            ),
        ).toBe('4 minutes ago');
    });

    it('formats updated_at as yesterday when previous day', () => {
        expect(
            formatAsRelativeTime(
                '2026-04-29 18:05:18',
                new Date(2026, 3, 30, 9, 0, 0),
            ),
        ).toBe('yesterday');
    });

    it('formats updated_at as relative year', () => {
        expect(
            formatAsRelativeTime(
                '2025-04-29 18:05:18',
                new Date(2026, 3, 29, 18, 5, 18),
            ),
        ).toBe('a year ago');
    });

    it('falls back to raw value when date cannot be parsed', () => {
        expect(formatAsRelativeTime('not-a-date')).toBe('not-a-date');
    });

    it('formats currency value from resolved data', () => {
        expect(formatMetricValue(metricWidget())).toContain('1,250');
    });

    it('formats trend percent with sign', () => {
        expect(formatTrendPercent(metricWidget())).toBe('+12.5%');
    });

    it('uses provided trend comment first', () => {
        expect(metricTrendComment(metricWidget())).toBe(
            'Trending up this month',
        );
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

describe('relative-time locale', () => {
    it('supports explicit locale configuration hook', () => {
        expect(() => setRelativeTimeLocale('en')).not.toThrow();
    });
});
